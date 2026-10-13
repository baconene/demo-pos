<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { Head } from '@inertiajs/vue3'
import { toast } from 'vue-sonner'
import { Printer, RefreshCw, CheckCircle2, AlertCircle } from 'lucide-vue-next'
import {
    loadPrintSettings, savePrintSettings, getQZPrinters,
    printReceipt,
    type PrintSettings,
} from '@/utils/printReceipt'

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Settings', href: '/settings/profile' },
            { title: 'Printing', href: '/settings/printing' },
        ],
    },
})

const settings = ref<PrintSettings>(loadPrintSettings())
const availablePrinters = ref<string[]>([])
const loadingPrinters = ref(false)
const qzStatus = ref<'unknown' | 'connected' | 'error'>('unknown')

const save = () => {
    savePrintSettings(settings.value)
    toast.success('Print settings saved')
}

const detectPrinters = async () => {
    loadingPrinters.value = true
    qzStatus.value = 'unknown'
    try {
        availablePrinters.value = await getQZPrinters()
        qzStatus.value = 'connected'
        if (availablePrinters.value.length === 0) {
            toast.info('QZ Tray connected but no printers found')
        }
    } catch (err: any) {
        qzStatus.value = 'error'
        toast.error('Could not connect to QZ Tray. Make sure it is installed and running.')
    } finally {
        loadingPrinters.value = false
    }
}

const testPrint = async () => {
    await printReceipt({
        orderId: 0,
        queueNumber: 99,
        orderType: 'takeout',
        tableNumber: null,
        customerName: 'Test Customer',
        customerContact: null,
        customerAddress: null,
        notes: 'This is a test receipt',
        items: [
            { name: 'Baby Back Ribs', quantity: 2, unit_price: 299 },
            { name: 'Liempo', quantity: 1, unit_price: 189 },
        ],
        subtotal: 787,
        discount: 0,
        total: 787,
        tenderName: 'Cash',
        amountTendered: 800,
        change: 13,
        paid: true,
    })
}

onMounted(() => {
    settings.value = loadPrintSettings()
})
</script>

<template>
    <Head title="Printing Settings" />

    <div class="space-y-6">
        <div>
            <h2 class="text-base font-semibold">Printing</h2>
            <p class="text-sm text-muted-foreground mt-0.5">Configure receipt printing for your POS terminal.</p>
        </div>

        <!-- Paper Size -->
        <div class="rounded-xl border bg-card shadow-sm p-5 space-y-4">
            <h3 class="font-semibold text-sm flex items-center gap-2">
                <Printer class="h-4 w-4" /> Paper Settings
            </h3>

            <div>
                <label class="text-xs font-medium text-muted-foreground block mb-2">Paper Width</label>
                <div class="flex gap-3">
                    <label
                        v-for="opt in [{ val: '57mm', label: '57 mm', desc: 'Standard POS / portable' }, { val: '80mm', label: '80 mm', desc: 'Wide thermal roll' }]"
                        :key="opt.val"
                        :class="[
                            'flex-1 flex items-start gap-3 rounded-lg border p-3 cursor-pointer transition',
                            settings.paperWidth === opt.val
                                ? 'border-primary bg-primary/5'
                                : 'border-border hover:bg-muted/50',
                        ]"
                    >
                        <input type="radio" :value="opt.val" v-model="settings.paperWidth" class="mt-0.5" />
                        <div>
                            <p class="text-sm font-semibold">{{ opt.label }}</p>
                            <p class="text-xs text-muted-foreground">{{ opt.desc }}</p>
                        </div>
                    </label>
                </div>
            </div>
        </div>

        <!-- Print Destination -->
        <div class="rounded-xl border bg-card shadow-sm p-5 space-y-4">
            <div class="flex items-start justify-between">
                <div>
                    <h3 class="font-semibold text-sm">Print Destination</h3>
                    <p class="text-xs text-muted-foreground mt-0.5">Use QZ Tray for silent printing without a browser dialog.</p>
                </div>
                <label class="relative inline-flex items-center cursor-pointer">
                    <input type="checkbox" v-model="settings.useQZTray" class="sr-only peer" />
                    <div class="w-10 h-5 bg-muted rounded-full peer peer-checked:bg-primary transition-colors after:content-[''] after:absolute after:top-0.5 after:left-0.5 after:bg-white after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:after:translate-x-5"></div>
                </label>
            </div>

            <!-- QZ Tray setup -->
            <template v-if="settings.useQZTray">
                <!-- Status indicator -->
                <div :class="[
                    'flex items-center gap-2 rounded-lg px-3 py-2 text-xs font-medium',
                    qzStatus === 'connected' ? 'bg-green-50 text-green-700 dark:bg-green-950/20 dark:text-green-400' :
                    qzStatus === 'error' ? 'bg-red-50 text-red-700 dark:bg-red-950/20 dark:text-red-400' :
                    'bg-muted text-muted-foreground',
                ]">
                    <CheckCircle2 v-if="qzStatus === 'connected'" class="h-3.5 w-3.5" />
                    <AlertCircle v-else-if="qzStatus === 'error'" class="h-3.5 w-3.5" />
                    <Printer v-else class="h-3.5 w-3.5" />
                    <span v-if="qzStatus === 'connected'">QZ Tray connected</span>
                    <span v-else-if="qzStatus === 'error'">QZ Tray not reachable — is it running?</span>
                    <span v-else>QZ Tray status unknown</span>
                </div>

                <!-- Printer name -->
                <div>
                    <label class="text-xs font-medium text-muted-foreground block mb-1">Printer Name</label>
                    <div class="flex gap-2">
                        <select
                            v-if="availablePrinters.length > 0"
                            v-model="settings.printerName"
                            class="flex-1 rounded-lg border bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary"
                        >
                            <option value="">— Select printer —</option>
                            <option v-for="p in availablePrinters" :key="p" :value="p">{{ p }}</option>
                        </select>
                        <input
                            v-else
                            v-model="settings.printerName"
                            type="text"
                            placeholder="e.g. EPSON TM-P20II"
                            class="flex-1 rounded-lg border bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary"
                        />
                        <button
                            @click="detectPrinters"
                            :disabled="loadingPrinters"
                            class="flex items-center gap-1.5 rounded-lg border px-3 py-2 text-sm font-medium hover:bg-muted disabled:opacity-50"
                        >
                            <RefreshCw :class="['h-3.5 w-3.5', loadingPrinters && 'animate-spin']" />
                            Detect
                        </button>
                    </div>
                    <p class="text-xs text-muted-foreground mt-1">
                        Click <strong>Detect</strong> to auto-discover printers (requires QZ Tray running), or type the exact Windows printer name.
                    </p>
                </div>

                <!-- Setup instructions -->
                <div class="rounded-lg bg-muted/50 p-3 space-y-1">
                    <p class="text-xs font-semibold">QZ Tray Setup</p>
                    <ol class="text-xs text-muted-foreground space-y-0.5 list-decimal list-inside">
                        <li>Download and install QZ Tray from <span class="font-medium">qz.io/download</span></li>
                        <li>Launch QZ Tray — it runs in the system tray</li>
                        <li>Connect your thermal printer via USB or Bluetooth</li>
                        <li>Click <strong>Detect</strong> above to find your printer</li>
                        <li>Save settings and test print</li>
                    </ol>
                </div>
            </template>

            <!-- Browser mode note -->
            <div v-else class="rounded-lg bg-muted/40 px-3 py-2 text-xs text-muted-foreground">
                Browser print dialog will appear on each receipt. The browser remembers your last selected printer and paper size automatically.
            </div>
        </div>

        <!-- Actions -->
        <div class="flex items-center gap-3">
            <button
                @click="save"
                class="rounded-lg bg-primary px-5 py-2 text-sm font-bold text-primary-foreground hover:bg-primary/90"
            >
                Save Settings
            </button>
            <button
                @click="testPrint"
                class="flex items-center gap-1.5 rounded-lg border px-4 py-2 text-sm font-medium hover:bg-muted"
            >
                <Printer class="h-3.5 w-3.5" /> Test Print
            </button>
        </div>
    </div>
</template>
