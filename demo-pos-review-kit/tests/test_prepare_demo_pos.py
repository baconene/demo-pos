import importlib.util
import tempfile
import unittest
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
spec = importlib.util.spec_from_file_location('prepare_demo_pos', ROOT / 'prepare_demo_pos.py')
kit = importlib.util.module_from_spec(spec)
spec.loader.exec_module(kit)


class PreparedChangeTests(unittest.TestCase):
    def setUp(self):
        self.temp = tempfile.TemporaryDirectory()
        self.root = Path(self.temp.name)

    def tearDown(self):
        self.temp.cleanup()

    def write(self, name, text):
        path = self.root / name
        path.parent.mkdir(parents=True, exist_ok=True)
        path.write_text(text, encoding='utf-8')
        return path

    def test_env_has_no_real_credentials_and_expected_defaults(self):
        env = (ROOT / '.env.example').read_text()
        self.assertIn('APP_NAME="DEMO POS"', env)
        self.assertIn('The stage script applies dine_in', env)
        self.assertIn('DB_PASSWORD=\n', env)
        self.assertIn('APP_KEY=\n', env)
        self.assertIn('PUSHER_APP_SECRET=\n', env)
        self.assertNotIn('bypassgrill.baconologies.com', env)

    def test_web_and_server_conditional_rules(self):
        req = 'class A { rules() { return [\n' + "'table_number' => 'nullable|string',\n'customer_name' => 'nullable|string|max:100',\n'customer_contact' => 'nullable|string|max:50',\n'customer_address' => 'nullable|string|max:300'," + '\n]; } }'
        self.write('app/Http/Requests/StoreOrderRequest.php', req)
        kit.patch_order_store(self.root)
        s = (self.root / 'app/Http/Requests/StoreOrderRequest.php').read_text()
        self.assertIn('required_if:order_type,dine_in', s)
        self.assertIn('required_if:order_type,takeout,delivery', s)
        self.assertEqual(s.count('required_if:order_type,delivery'), 2)

    def test_mobile_batch_validation_is_per_order(self):
        data = "function sync() {$results=[];foreach($data['orders']??[] as $payload){$existing=DB::table('x')->first();if($existing){$results[]=['status'=>'already_synced'];continue;}try{$order=DB::transaction(function(){});}}}"
        self.write('app/Http/Controllers/Api/V1/MobilePosController.php', data)
        kit.patch_mobile_sync(self.root)
        s = (self.root / 'app/Http/Controllers/Api/V1/MobilePosController.php').read_text()
        self.assertIn('Validator::make($payload', s)
        self.assertIn("'status'=>'error'", s)
        self.assertIn("'table_number'=>'required_if:order_type,dine_in", s)
        self.assertTrue(s.index('already_synced') < s.index('Validator::make') < s.index('DB::transaction'))

    def test_default_order_type_reset(self):
        self.write('resources/js/stores/cartStore.ts', "const orderType = ref('')\norderType.value = ''")
        self.write('resources/js/pages/CashierDashboard.vue', "const orderHint = computed(() => !cartStore.items.length ? 'Choose products to start an order.' : !cartStore.orderType ? 'Select an order type to continue.' : !cartStore.customerName.trim() ? 'Add a customer name to continue.' : 'Ready to review payment.')\n" + "    if (!cartStore.customerName.trim()) {\n        orderTypeOpen.value = true\n        toast.error('Customer name is required.')\n        return\n    }")
        kit.patch_web_pos(self.root)
        self.assertIn("ref('dine_in')", (self.root / 'resources/js/stores/cartStore.ts').read_text())
        self.assertIn("orderType.value = 'dine_in'", (self.root / 'resources/js/stores/cartStore.ts').read_text())
        s = (self.root / 'resources/js/pages/CashierDashboard.vue').read_text()
        self.assertIn('Table number is required for dine-in',s)
        self.assertIn('Contact number is required for delivery',s)

    def test_android_creation_rules_and_checkout_reset(self):
        text = "const[type,setType]=useState(''); " + "if(!customer.trim())return Alert.alert('Customer required','Add the customer name or alias.');" + "if(type==='delivery'&&!contact.trim())" + "setType('');setCustomer('');" + ">BG</Text>"
        self.write('android-app/app/pos.tsx', text)
        self.write('android-app/src/api/mobilePos.ts', "const API_BASE='https://demo-pos.baconologies.com/api/v1/mobile-pos';\nconst TOKEN_KEY='bypass_grill_mobile_token';")
        self.write('android-app/app/syncing.tsx', '>BYPASS</Text>>GRILL</Text>')
        kit.patch_android_pos(self.root)
        s = (self.root / 'android-app/app/pos.tsx').read_text()
        self.assertIn("setType('dine_in')", s)
        self.assertIn("type==='dine_in'&&!table.trim()", s)
        self.assertIn("type==='takeout'||type==='delivery'", s)
        self.assertIn('>DP</Text>', s)
        s = (self.root / 'android-app/src/api/mobilePos.ts').read_text()
        self.assertIn('process.env.EXPO_PUBLIC_API_URL',s)
        self.assertIn('demo_pos_mobile_token',s)

    def test_seeding_only_general_permissions_and_tenders(self):
        self.write('database/seeders/DatabaseSeeder.php', "<?php\n        $this->call([\n UserSeeder::class,\n CategorySeeder::class,\n        ]);")
        self.write('database/seeders/UserSeeder.php', "<?php class UserSeeder { $password = 'unsafe-example'; }")
        self.write('database/seeders/AllUsersSeeder.php', "<?php class AllUsersSeeder { $password = 'unsafe-example'; }")
        kit.remove_sensitive_seeds(self.root)
        s = (self.root / 'database/seeders/DatabaseSeeder.php').read_text()
        self.assertIn('RolePermissionSeeder::class', s)
        self.assertIn('PaymentTenderSeeder::class', s)
        self.assertNotIn('UserSeeder::class',s)
        for seed in ('UserSeeder','AllUsersSeeder'):
            self.assertNotIn('unsafe-example',(self.root/f'database/seeders/{seed}.php').read_text())

    def test_git_state_and_secrets_are_never_copied(self):
        source=self.root/'source'; source.mkdir()
        self.write('source/artisan','php artisan')
        self.write('source/android-app/app.json','{}')
        self.write('source/.env','DB_PASSWORD=secret')
        self.write('source/.git/config','git internal')
        self.write('source/node_modules/existing','node deps')
        self.write('source/resources/index.php','<?php')
        out=self.root/'output'
        kit.sanitize_source_tree(source,out)
        self.assertTrue((out/'artisan').exists())
        self.assertTrue((out/'resources/index.php').exists())
        self.assertFalse((out/'.env').exists())
        self.assertFalse((out/'.git').exists())
        self.assertFalse((out/'node_modules').exists())
        with self.assertRaises(FileExistsError):
            kit.sanitize_source_tree(source,out)


if __name__ == '__main__':
    unittest.main()