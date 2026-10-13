#!/usr/bin/env python3
"""Create a local, no-push DEMO POS staging tree from a Bypass Grill checkout.

Read-only git HEAD verification only; no network, git writes, Forge calls or secrets.
Run from a local source checkout pinned to client.json:source_commit.
"""
from __future__ import annotations

import argparse
import json
import re
import shutil
import subprocess
from pathlib import Path

HERE = Path(__file__).resolve().parent
CLIENT = json.loads((HERE / "client.json").read_text(encoding="utf-8"))

IGNORED_NAMES = {
    ".git", ".claude", ".idea", ".vscode", ".env", ".env.local", ".env.production",
    "node_modules", "vendor", "dist", "build", ".expo", ".gradle", ".phpunit.cache",
    ".cache", "coverage", "__pycache__", "storage", "bootstrap/cache", "database.sqlite",
    "android", "ios", "captures", "screenshots"
}
EXCLUDED_PREFIXES = (
    "public/images/demo/", "public/images/welcome/", "scripts/demo/",
    "database/seeders/data/", ".claude/"
)
SAFE_TEXT_EXTENSIONS = {".vue", ".tsx", ".ts", ".js", ".jsx", ".php", ".md", ".json", ".yml", ".yaml", ".css", ".svg"}
SOURCE_SPECIFIC_SEEDERS = [
    "UserSeeder", "AllUsersSeeder", "CategorySeeder", "ProductSeeder", "IngredientSeeder",
    "RecipeSeeder", "ModifierSeeder", "SupplierSeeder", "PageSectionSeeder",
    "PreparedFoodCatalogSeeder", "BypassGrillInventorySeeder",
]


def replace_checked(contents: str, old: str, new: str, path: str, *, required: bool = True) -> str:
    if old not in contents:
        if required:
            raise ValueError(f"Source drift in {path}: expected snippet not found: {old[:130]!r}")
        return contents
    return contents.replace(old, new)


def is_excluded(path: Path, root: Path) -> bool:
    rel = path.relative_to(root).as_posix()
    if rel.startswith(EXCLUDED_PREFIXES):
        return True
    return any(part in IGNORED_NAMES or part.startswith(".env.") and part != ".env.example" for part in path.relative_to(root).parts)


def sanitize_source_tree(source: Path, output: Path) -> None:
    if output.exists():
        raise FileExistsError(f"Refusing to overwrite existing destination: {output}")
    if not (source / "artisan").exists() or not (source / "android-app" / "app.json").exists():
        raise ValueError("Expected Bypass Grill checkout with artisan and android-app/app.json")
    if output.resolve() == source.resolve() or source.resolve() in output.resolve().parents:
        raise ValueError("Destination must not be the source or a child of the source")

    def ignore(dirpath: str, names: list[str]) -> set[str]:
        parent = Path(dirpath)
        return {name for name in names if is_excluded(parent / name, source)}

    shutil.copytree(source, output, ignore=ignore)
    # Preserve the framework's writable directory structure without copying source data.
    for relative in (
        "storage/app/private", "storage/app/public", "storage/framework/cache/data",
        "storage/framework/sessions", "storage/framework/testing",
        "storage/framework/views", "storage/logs", "bootstrap/cache"
    ):
        folder = output / relative
        folder.mkdir(parents=True, exist_ok=True)
        (folder / ".gitignore").write_text("*\n!.gitignore\n", encoding="utf-8")


def transform_file(root: Path, path: str, func) -> None:
    f = root / path
    if not f.exists():
        raise FileNotFoundError(f"Missing expected master-source file: {path}")
    text = f.read_text(encoding="utf-8")
    result = func(text)
    if not isinstance(result, str):
        raise TypeError("Transformation must return str")
    f.write_text(result, encoding="utf-8")


def replace_branding(root: Path) -> int:
    count = 0
    replacements = [
        ("BYPASS GRILL", "DEMO POS"), ("Bypass Grill", "DEMO POS"),
        ("Bypass grill", "DEMO POS"),
        ("bypassgrill.baconologies.com", "demo-pos.baconologies.com"),
        ("admin@bypassgrill.local", "admin@demo-pos.invalid"),
        ("GOOD FOOD. GOOD MOOD.", "SIMPLE. FAST. RELIABLE."),
    ]
    for f in root.rglob("*"):
        if not f.is_file() or f.suffix.lower() not in SAFE_TEXT_EXTENSIONS:
            continue
        if f.is_relative_to(root / "tests") or f.is_relative_to(root / "database/seeders"):
            continue
        text = f.read_text(encoding="utf-8")
        new = text
        for old, replacement in replacements:
            new = new.replace(old, replacement)
        if new != text:
            count += 1
            f.write_text(new, encoding="utf-8")
    return count


def patch_order_store(root: Path) -> None:
    p = "app/Http/Requests/StoreOrderRequest.php"
    def change(s: str) -> str:
        replacements = [
            ("'table_number' => 'nullable|string'", "'table_number' => 'required_if:order_type,dine_in|nullable|string|max:50'"),
            ("'customer_name' => 'nullable|string|max:100'", "'customer_name' => 'required_if:order_type,takeout,delivery|nullable|string|max:100'"),
            ("'customer_contact' => 'nullable|string|max:50'", "'customer_contact' => 'required_if:order_type,delivery|nullable|string|max:50'"),
            ("'customer_address' => 'nullable|string|max:300'", "'customer_address' => 'required_if:order_type,delivery|nullable|string|max:300'"),
        ]
        for a,b in replacements:
            s=replace_checked(s,a,b,p)
        return s
    transform_file(root,p,change)


def patch_mobile_sync(root: Path) -> None:
    p = "app/Http/Controllers/Api/V1/MobilePosController.php"
    # Deliberately return a per-order error instead of failing a whole offline batch.
    validator = """$check=\\Illuminate\\Support\\Facades\\Validator::make($payload,[
            'order_type'=>'required|in:dine_in,takeout,delivery',
            'table_number'=>'required_if:order_type,dine_in|nullable|string|max:50',
            'customer_name'=>'required_if:order_type,takeout,delivery|nullable|string|max:100',
            'customer_contact'=>'required_if:order_type,delivery|nullable|string|max:50',
            'customer_address'=>'required_if:order_type,delivery|nullable|string|max:300',
        ]);if($check->fails()){$results[]=['client_id'=>$payload['client_id'],'status'=>'error','message'=>$check->errors()->first()];continue;}"""
    def change(s: str) -> str:
        anchor="'status'=>'already_synced'];continue;}try{$order=DB::transaction"
        return replace_checked(s,anchor,"'status'=>'already_synced'];continue;}"+validator+"try{$order=DB::transaction",p)
    transform_file(root,p,change)


def patch_web_pos(root: Path) -> None:
    p = "resources/js/stores/cartStore.ts"
    transform_file(root,p,lambda s: replace_checked(
        replace_checked(s,"const orderType = ref('')","const orderType = ref('dine_in')",p),
        "orderType.value = ''","orderType.value = 'dine_in'",p))

    p = "resources/js/pages/CashierDashboard.vue"
    def change(s: str) -> str:
        old="""    if (!cartStore.customerName.trim()) {
        orderTypeOpen.value = true
        toast.error('Customer name is required.')
        return
    }"""
        new="""    if (cartStore.orderType === 'dine_in' && !cartStore.tableNumber?.trim()) {
        orderTypeOpen.value = true
        toast.error('Table number is required for dine-in orders.')
        return
    }
    if (['takeout', 'delivery'].includes(cartStore.orderType) && !cartStore.customerName.trim()) {
        orderTypeOpen.value = true
        toast.error('Customer name or alias is required for this order type.')
        return
    }
    if (cartStore.orderType === 'delivery' && !cartStore.customerContact.trim()) {
        orderTypeOpen.value = true
        toast.error('Contact number is required for delivery.')
        return
    }
    if (cartStore.orderType === 'delivery' && !cartStore.customerAddress.trim()) {
        orderTypeOpen.value = true
        toast.error('Delivery address is required.')
        return
    }"""
        s=replace_checked(s,old,new,p)
        # Optional customer is no longer the universal readiness criterion.
        hint_pattern=r"const orderHint = computed\(\(\) => [^\n]*\)"
        hint_new="""const orderHint = computed(() => {
    if (!cartStore.items.length) return 'Choose products to start an order.'
    if (!cartStore.orderType) return 'Select an order type to continue.'
    if (cartStore.orderType === 'dine_in' && !cartStore.tableNumber?.trim()) return 'Add a table number to continue.'
    if (['takeout', 'delivery'].includes(cartStore.orderType) && !cartStore.customerName.trim()) return 'Add a customer name or alias to continue.'
    if (cartStore.orderType === 'delivery' && !cartStore.customerContact.trim()) return 'Add a delivery contact number.'
    if (cartStore.orderType === 'delivery' && !cartStore.customerAddress.trim()) return 'Add a delivery address.'
    return 'Ready to review payment.'
})"""
        s,n=re.subn(hint_pattern,lambda _:hint_new,s,count=1)
        if n!=1: raise ValueError(f"Source drift in {p}: orderHint not found")
        s=s.replace("Let's get <em>grilling.</em>","Let's take <em>orders.</em>")
        s=s.replace("<Flame :size=\"14\"", "<ShoppingBag :size=\"14\"") if 'ShoppingBag' in s else s
        return s
    transform_file(root,p,change)


def patch_android_pos(root: Path) -> None:
    p="android-app/app/pos.tsx"
    def change(s: str) -> str:
        changes=[
            ("[type,setType]=useState('')", "[type,setType]=useState('dine_in')"),
            ("setType('');setCustomer('')", "setType('dine_in');setCustomer('')"),
            ("if(!customer.trim())return Alert.alert('Customer required','Add the customer name or alias.');",
             "if((type==='takeout'||type==='delivery')&&!customer.trim())return Alert.alert('Customer required','Add the customer name or alias.');"),
            ("if(type==='delivery'&&!contact.trim())", "if(type==='dine_in'&&!table.trim())return Alert.alert('Table required','Add a table number for dine-in.');if(type==='delivery'&&!contact.trim())"),
            (">BG</Text>", ">DP</Text>"),
        ]
        for a,b in changes: s=replace_checked(s,a,b,p)
        return s
    transform_file(root,p,change)

    p="android-app/app/syncing.tsx"
    def sync_label(s: str) -> str:
        return replace_checked(replace_checked(s,">BYPASS</Text>",">DEMO</Text>",p),">GRILL</Text>",">POS</Text>",p)
    transform_file(root,p,sync_label)

    p="android-app/src/api/mobilePos.ts"
    def change(s: str) -> str:
        s=replace_checked(s,"const API_BASE='https://bypassgrill.baconologies.com/api/v1/mobile-pos';",
            "const API_BASE=(process.env.EXPO_PUBLIC_API_URL || 'https://demo-pos.baconologies.com/api/v1').replace(/\\/$/, '') + '/mobile-pos';",p,required=False) if "const API_BASE='https://bypassgrill.baconologies.com/api/v1/mobile-pos';" in s else s
        # replace_branding may have changed the URL already.
        s=replace_checked(s,"const API_BASE='https://demo-pos.baconologies.com/api/v1/mobile-pos';",
            "const API_BASE=(process.env.EXPO_PUBLIC_API_URL || 'https://demo-pos.baconologies.com/api/v1').replace(/\\/$/, '') + '/mobile-pos';",p,required=False)
        if "process.env.EXPO_PUBLIC_API_URL" not in s: raise ValueError("Android API_BASE transformation did not complete")
        s=replace_checked(s,"const TOKEN_KEY='bypass_grill_mobile_token';","const TOKEN_KEY='demo_pos_mobile_token';",p)
        return s
    transform_file(root,p,change)


def patch_android_identity(root: Path) -> None:
    p=root/"android-app/app.json"
    settings=json.loads(p.read_text(encoding="utf-8")); expo=settings["expo"]
    expo.update(name="DEMO POS",slug="demo-pos",scheme="demopos",userInterfaceStyle="light")
    expo["android"]["package"]="com.baconologies.demopos"
    expo["android"]["adaptiveIcon"]["backgroundColor"]="#F8FAFC"
    p.write_text(json.dumps(settings,indent=2)+"\n",encoding="utf-8")
    p=root/"android-app/package.json"; settings=json.loads(p.read_text(encoding="utf-8"))
    settings["name"]="demo-pos"
    p.write_text(json.dumps(settings,indent=2)+"\n",encoding="utf-8")
    shutil.copy2(HERE/"assets/demo-pos-icon.png",root/"android-app/assets/icon.png")
    p=root/".github/workflows/android-apk.yml"
    s=p.read_text(encoding="utf-8");s=replace_checked(s,"branches: [feature/android-pos-app]","branches: [main]",p.as_posix());s=replace_checked(s,"bypass-grill-pos-release-apk","demo-pos-release-apk",p.as_posix());p.write_text(s,encoding="utf-8")
    (root/"android-app/.env.example").write_text((HERE/"android.env.example").read_text(encoding="utf-8"),encoding="utf-8")


def remove_sensitive_seeds(root: Path) -> None:
    p="database/seeders/DatabaseSeeder.php"
    def change(s:str)->str:
        start=s.find("        $this->call([")
        end=s.find("        ]);",start)
        if start<0 or end<0:raise ValueError(f"Source drift: cannot locate {p} seeder list")
        return s[:start]+"        $this->call([\n            RolePermissionSeeder::class,\n            PaymentTenderSeeder::class,\n        ]);"+s[end+len("        ]);"):]
    transform_file(root,p,change)

    # Neutralize former business-specific seed classes, not just their inclusion in DatabaseSeeder.
    for cls in SOURCE_SPECIFIC_SEEDERS:
        p=root/f"database/seeders/{cls}.php"
        if p.exists():
            p.write_text("<?php\n\nnamespace Database\\Seeders;\n\nuse Illuminate\\Database\\Seeder;\n\n/** Client setup: sample source business data disabled. */\nclass "+cls+" extends Seeder\n{\n    public function run(): void\n    {\n        // Populate business inventory and users through the client admin setup.\n    }\n}\n",encoding="utf-8")


def patch_print_settings(root: Path) -> None:
    p="app/Models/PrintServiceSetting.php"
    def change(s: str)->str:
        s=replace_checked(s,"'print_service_url'   => 'http://192.168.1.42:8080'","'print_service_url'   => ''",p)
        s=replace_checked(s,"'print_store_name'    => ''","'print_store_name'    => 'DEMO POS'",p)
        s=replace_checked(s,"'print_store_address' => ''","'print_store_address' => 'DEMO ADDRESS'",p)
        return s
    transform_file(root,p,change)
    p="database/migrations/2026_06_03_000001_create_print_service_settings_table.php"
    transform_file(root,p,lambda s: replace_checked(s,"->default('http://192.168.1.42:8080')","->default('')",p))


def patch_identity(root: Path) -> None:
    p="resources/js/app.ts"
    def change(s: str)->str:
        s=replace_checked(s,"import GrillAuthLayout from '@/layouts/auth/GrillAuthLayout.vue';\n", "",p)
        s=replace_checked(s,"return GrillAuthLayout;","return AuthLayout;",p)
        s=replace_checked(s,"const appName = import.meta.env.VITE_APP_NAME || 'Laravel';","const appName = import.meta.env.VITE_APP_NAME || 'DEMO POS';",p)
        return s
    transform_file(root,p,change)
    p="resources/js/components/AppLogo.vue"
    content="""<script setup lang=\"ts\">
import { usePage } from '@inertiajs/vue3';
import { Store } from 'lucide-vue-next';
import { computed } from 'vue';
const page = usePage<{logoUrl?: string | null; brandName?: string | null}>();
const logoUrl = computed(() => page.props.logoUrl);
const brandName = computed(() => page.props.brandName || 'DEMO POS');
</script>
<template>
  <div class=\"flex aspect-square size-8 shrink-0 items-center justify-center overflow-hidden rounded-lg bg-slate-700 text-white\">
    <img v-if=\"logoUrl\" :src=\"logoUrl\" class=\"size-8 object-contain\" alt=\"\" />
    <Store v-else class=\"size-5\" aria-hidden=\"true\" />
  </div>
  <div class=\"ml-1 grid min-w-0 flex-1 text-left text-sm group-data-[collapsible=icon]:hidden\">
    <span class=\"truncate text-[13px] font-bold tracking-tight\">{{ brandName }}</span>
    <span class=\"mt-1 text-[9px] font-medium tracking-wide opacity-65\">POINT OF SALE</span>
  </div>
</template>
"""
    (root/p).write_text(content,encoding="utf-8")
    shutil.copy2(HERE/"assets/demo-pos-icon.png",root/"public/apple-touch-icon.png")
    shutil.copy2(HERE/"assets/demo-pos-icon.ico",root/"public/favicon.ico")
    p="public/favicon.svg"
    (root/p).write_text('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64"><rect width="64" height="64" rx="14" fill="#334155"/><text x="32" y="42" fill="white" font-family="Arial, sans-serif" font-weight="700" font-size="24" text-anchor="middle">DP</text></svg>\n',encoding="utf-8")
    # Preserve existing public menu/cart behavior while neutralizing marketing copy in review.
    p="resources/js/pages/Welcome.vue"
    def welcome(s: str) -> str:
        for a,b in [
            ("Pork Monster Ribs", "Explore Our Menu"), ("PORK MONSTER RIBS", "DEMO POS"),
            ("PORK.<br />MONSTER.<br /><span>RIBS.</span>", "YOUR.<br />MENU.<br /><span>READY.</span>"),
            ("Big on flavor. Serious about the grill.", "Explore the latest items on our menu."),
            ("Bring your appetite. We'll bring the ribs.", "Build your list and ask our team for availability."),
            ("For the love of the grill.", "See what we have in store."),
            ("THE BOYS", "DEMO POS"),("A little char. A lot of appetite.", "Browse our latest selection."),
            ("FRESH OFF THE GRILL", "AVAILABLE ON OUR MENU"),
            ("All on the grill", "Everything"),
            ("PORK MONSTER RIBS", "DEMO POS"),
            ("Our grill", "About us"), ("our grill", "our store"),
            ("GRILL IT. GLAZE IT. CRAVE IT.", "EXPLORE. DISCOVER. ENJOY."),
            ("Big rib cravings?", "Looking for something?")]:
            s=s.replace(a,b)
        s=s.replace("'/images/welcome/ribs-plate.jpg'","'/images/demo-pos/placeholder.svg'")
        s=s.replace("'/images/welcome/ribs-grill.jpg'","'/images/demo-pos/placeholder.svg'")
        s=s.replace('src="/images/welcome/ribs-plate.jpg"','src="/images/demo-pos/placeholder.svg"')
        s=s.replace('src="/images/welcome/ribs-grill.jpg"','src="/images/demo-pos/placeholder.svg"')
        s=s.replace("const facebook = 'https://www.facebook.com/profile.php?id=61588899475779';",
                    "const facebook = ''; // Pending client-approved ordering/social channel")
        # Disable external send action when no client channel is available (copy order remains).
        s=s.replace(':href="facebook"','v-if="facebook" :href="facebook"')
        s=s.replace("const signature = computed(() =>\n    products.value.find((p) => /rib/i.test(p.name)),\n);",
                    "const signature = computed(() => products.value[0]);")
        return s
    transform_file(root,p,welcome)
    (root/"public/images/demo-pos").mkdir(parents=True,exist_ok=True)
    (root/"public/images/demo-pos/placeholder.svg").write_text('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 900 600"><rect width="900" height="600" fill="#e2e8f0"/><rect x="45" y="45" width="810" height="510" rx="24" fill="#f8fafc" stroke="#cbd5e1" stroke-width="3"/><text x="450" y="282" text-anchor="middle" font-size="82" font-weight="700" font-family="Arial, sans-serif" fill="#334155">DEMO POS</text><text x="450" y="350" text-anchor="middle" font-size="24" font-family="Arial, sans-serif" letter-spacing="5" fill="#64748b">PRODUCT PHOTO COMING SOON</text></svg>\n',encoding="utf-8")
    p="resources/css/app.css"
    with (root/p).open("a",encoding="utf-8") as out:
        out.write("\n/* DEMO POS neutral minimalist client tokens (client override) */\n:root { --background:#f8fafc; --foreground:#0f172a; --card:#fff; --card-foreground:#0f172a; --primary:#334155; --primary-foreground:#fff; --secondary:#e2e8f0; --secondary-foreground:#1e293b; --muted:#f1f5f9; --muted-foreground:#64748b; --accent:#e2e8f0; --accent-foreground:#334155; --border:#cbd5e1; --input:#cbd5e1; --ring:#64748b; --sidebar-background:#f8fafc; --sidebar-foreground:#334155; --sidebar-primary:#334155; --sidebar-primary-foreground:#fff; --sidebar-accent:#e2e8f0; --sidebar-accent-foreground:#334155; --sidebar-border:#e2e8f0; }\n")


def patch_offline_forge_build(root: Path) -> None:
    """Do not fetch remote Bunny fonts at Vite build time (Forge may block fetch)."""
    p = "vite.config.ts"
    def change(s: str) -> str:
        s=replace_checked(s,"import { bunny } from 'laravel-vite-plugin/fonts';\n","",p)
        snippet="""            fonts: [
                bunny('Instrument Sans', {
                    weights: [400, 500, 600],
                }),
            ],
"""
        return replace_checked(s,snippet,"",p)
    transform_file(root,p,change)


def hide_source_only_public_routes(root: Path) -> None:
    """Omit source's Bypass Grill screenshot-based demo and unrelated public routes."""
    p = "routes/web.php"
    def change(s: str) -> str:
        start=s.find("// Public, read-only training pages.")
        stop=s.find("Route::get('/public/orders/{token}'",start)
        if start<0 or stop<0: raise ValueError("Expected source-only public demo routes not found")
        s=s[:start]+"// Source-specific promotional/demo pages are not published for this client.\n"+s[stop:]
        for name in ("printing-architecture","hi-tan","gumroadDescription"):
            expression=r"Route::inertia\('/"+name+r"',[\s\S]*?\)\s*->name\('[^']+'\);\s*"
            s=re.sub(expression,"",s,flags=re.DOTALL)
        return s
    transform_file(root,p,change)


def patch_workflows(root: Path) -> None:
    p=root/".github/workflows/tests.yml"
    if not p.exists(): return
    s=p.read_text(encoding="utf-8")
    # Keep Android-trigger and web tests aligned with destination branch main.
    s=s.replace("      - develop\n", "").replace("      - master\n", "").replace("      - workos\n", "")
    p.write_text(s,encoding="utf-8")


def write_env(root: Path) -> None:
    shutil.copy2(HERE/".env.example",root/".env.example")


def residual_branding(root: Path) -> list[dict[str,str|int]]:
    # This is a report, not automatic risky replacement of class names / paths.
    pat=re.compile(r"bypass[ -]?grill|the boys|pork monster|\bgrill\b|\bribs?\b",re.IGNORECASE)
    entries=[]
    for f in root.rglob("*"):
        if not f.is_file() or f.suffix.lower() not in SAFE_TEXT_EXTENSIONS:continue
        if f.is_relative_to(root/"tests"):continue
        relative=f.relative_to(root).as_posix()
        for num,line in enumerate(f.read_text(encoding="utf-8").splitlines(),1):
            if pat.search(line):
                entries.append({"file":relative,"line":num,"excerpt":line.strip()[:160]})
    return entries


def lint_staging(root: Path) -> None:
    expected=["artisan","resources/js/pages/CashierDashboard.vue","resources/js/stores/cartStore.ts","app/Http/Requests/StoreOrderRequest.php","app/Http/Controllers/Api/V1/MobilePosController.php","android-app/app.json","android-app/assets/icon.png",".github/workflows/android-apk.yml","resources/css/inventory-theme.css","resources/css/product-theme.css","resources/js/components/InventoryCostReport.vue","resources/js/components/InventoryDialog.vue","resources/js/components/RecipeBuilder.vue","resources/js/pages/reports/ServingTimeTab.vue"]
    missing=[p for p in expected if not (root/p).exists()]
    if missing: raise RuntimeError("Critical source dependencies missing: "+", ".join(missing))
    if (root/".env").exists(): raise RuntimeError("Secret .env must not appear in staging")


def main() -> None:
    parser=argparse.ArgumentParser(description="Stage an unpushed client POS for code review")
    parser.add_argument("--source",type=Path,required=True,help="Checked-out, trusted source branch folder")
    parser.add_argument("--destination",type=Path,required=True,help="NEW output directory; must not exist")
    args=parser.parse_args()
    source=args.source.expanduser().resolve()
    target=args.destination.expanduser().resolve()
    try:
        actual=subprocess.run(['git','-C',str(source),'rev-parse','HEAD'],
                              text=True,capture_output=True,check=True).stdout.strip()
    except (OSError,subprocess.CalledProcessError) as exc:
        raise RuntimeError('Source must be a Git checkout of the pinned upstream commit') from exc
    expected=CLIENT['source_commit']
    if actual != expected:
        raise RuntimeError(f'Source commit drift: expected {expected}, found {actual}; stage aborted')
    sanitize_source_tree(source,target)
    change_count=replace_branding(target)
    patch_order_store(target)
    patch_mobile_sync(target)
    patch_web_pos(target)
    patch_android_pos(target)
    patch_android_identity(target)
    remove_sensitive_seeds(target)
    patch_print_settings(target)
    patch_identity(target)
    patch_offline_forge_build(target)
    hide_source_only_public_routes(target)
    patch_workflows(target)
    write_env(target)
    test_path=target/"tests/Feature/DemoPosOrderFieldValidationTest.php"
    test_path.parent.mkdir(parents=True,exist_ok=True)
    shutil.copy2(HERE/"tests/DemoPosOrderFieldValidationTest.php",test_path)
    lint_staging(target)
    warnings=residual_branding(target)
    report={"client":CLIENT["business_name"],"source_ref":CLIENT["source_ref"],"source_commit":CLIENT["source_commit"],"destination_repository":CLIENT["repository"],"files_with_common_brand_replacements":change_count,"residual_branding_count":len(warnings),"residual_branding_examples":warnings[:250],"git_pushed":False,"built_or_deployed":False,"approval_required":True,"next_steps":["Review residual branding and strip source-specific business visuals","Run composer and npm install in staging","Run PHP tests and npm run build","Run Expo typecheck and release APK build","Confirm client admin onboarding and print service","Ask explicit user approval before any push"]}
    (target/"CLIENT_STAGING_REPORT.json").write_text(json.dumps(report,indent=2,ensure_ascii=False)+"\n",encoding="utf-8")
    print(json.dumps({"destination":str(target),"brand_replaced_in_files":change_count,"remaining_brand_refs":len(warnings),"pushed":False},indent=2))


if __name__ == "__main__":
    main()