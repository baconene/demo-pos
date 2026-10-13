<script setup lang="ts">
import { ref } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import { toast } from 'vue-sonner'
import { AlertTriangle, Trash2, X, ShieldCheck, PackageX } from 'lucide-vue-next'

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Settings', href: '/settings/profile' },
            { title: 'System', href: '/settings/system' },
        ],
    },
})

// ── Transaction Reset ──────────────────────────────────────────────────────────
const showResetModal = ref(false)
const resetText = ref('')
const resetting = ref(false)

const RESET_KEPT = [
    'User accounts & passwords',
    'Roles & permissions',
    'Menu products & categories',
    'Ingredients & recipes',
    'Modifiers',
    'Payment tenders',
    'Employee records',
]
const RESET_DELETED = [
    'All orders & order items',
    'All payments & refunds',
    'Queue numbers',
    'Inventory transaction history',
    'Financial transactions',
    'Bills & bill installments',
    'Payroll records',
    'Purchase orders',
    'Audit logs',
    'Inventory quantities (reset to 0)',
]

function openResetModal() {
    resetText.value = ''
    showResetModal.value = true
}

function executeReset() {
    if (resetText.value !== 'RESET') {
        toast.error('Type RESET exactly to proceed.')
        return
    }
    resetting.value = true
    router.post('/settings/system/reset', { confirmation: resetText.value }, {
        preserveScroll: true,
        onSuccess: () => {
            showResetModal.value = false
            toast.success('Transaction history cleared. Inventory quantities zeroed.')
        },
        onError: (errors) => {
            toast.error((Object.values(errors)[0] as string) ?? 'Reset failed.')
        },
        onFinish: () => { resetting.value = false },
    })
}

// ── Factory Reset ──────────────────────────────────────────────────────────────
const showFactoryModal = ref(false)
const factoryText = ref('')
const factoryResetting = ref(false)

const FACTORY_KEPT = [
    'User accounts & passwords',
    'Roles & permissions',
]
const FACTORY_DELETED = [
    'All orders, payments & refunds',
    'Queue numbers & audit logs',
    'Inventory transaction history',
    'Financial & payroll records',
    'Bills & bill installments',
    'All products & categories',
    'All ingredients & recipes',
    'All modifiers',
    'Payment tenders',
    'Employee records',
    'Suppliers',
]

function openFactoryModal() {
    factoryText.value = ''
    showFactoryModal.value = true
}

function executeFactoryReset() {
    if (factoryText.value !== 'FACTORY RESET') {
        toast.error('Type FACTORY RESET exactly to proceed.')
        return
    }
    factoryResetting.value = true
    router.post('/settings/system/factory-reset', { confirmation: factoryText.value }, {
        preserveScroll: true,
        onSuccess: () => {
            showFactoryModal.value = false
            toast.success('Factory reset complete. System is clean and ready for a fresh setup.')
        },
        onError: (errors) => {
            toast.error((Object.values(errors)[0] as string) ?? 'Factory reset failed.')
        },
        onFinish: () => { factoryResetting.value = false },
    })
}
</script>

<template>
    <Head title="System Settings" />

    <div class="space-y-8">
        <div>
            <h2 class="text-base font-semibold">System Settings</h2>
            <p class="text-sm text-muted-foreground mt-0.5">Manage system-level operations. Admin access only.</p>
        </div>

        <!-- Danger Zone -->
        <div class="rounded-xl border border-red-200 bg-red-50 dark:bg-red-950/20 dark:border-red-900/50 overflow-hidden">
            <div class="flex items-center gap-2 border-b border-red-200 dark:border-red-900/50 px-5 py-3 bg-red-100 dark:bg-red-950/40">
                <AlertTriangle class="h-4 w-4 text-red-600" />
                <span class="font-semibold text-sm text-red-700 dark:text-red-400">Danger Zone</span>
            </div>

            <div class="divide-y divide-red-200 dark:divide-red-900/50">

                <!-- ── Reset Transaction Data ───────────────────────────────── -->
                <div class="p-5 space-y-4">
                    <div class="flex flex-col sm:flex-row sm:items-start gap-4 justify-between">
                        <div>
                            <h3 class="font-semibold text-sm text-red-800 dark:text-red-300">Reset Transaction Data</h3>
                            <p class="text-xs text-red-700/80 dark:text-red-400/80 mt-1 max-w-md">
                                Clears all sales history — orders, payments, inventory logs, financial records, and payroll.
                                Menu setup, ingredients, employees, and payment tenders are kept.
                                Inventory quantities are zeroed. <strong>Irreversible.</strong>
                            </p>
                        </div>
                        <button
                            @click="openResetModal"
                            class="flex items-center gap-2 rounded-lg bg-red-600 hover:bg-red-700 px-4 py-2 text-sm font-semibold text-white transition shrink-0"
                        >
                            <Trash2 class="h-4 w-4" />
                            Reset Transactions
                        </button>
                    </div>
                    <div class="grid sm:grid-cols-2 gap-3">
                        <div class="rounded-lg bg-white dark:bg-background/50 border border-green-200 dark:border-green-900/40 p-3">
                            <p class="text-xs font-semibold text-green-700 dark:text-green-400 mb-1.5 flex items-center gap-1">
                                <ShieldCheck class="h-3.5 w-3.5" /> Kept
                            </p>
                            <ul class="space-y-1">
                                <li v-for="item in RESET_KEPT" :key="item" class="text-xs text-muted-foreground flex items-center gap-1.5">
                                    <span class="h-1.5 w-1.5 rounded-full bg-green-500 shrink-0"></span>{{ item }}
                                </li>
                            </ul>
                        </div>
                        <div class="rounded-lg bg-white dark:bg-background/50 border border-red-200 dark:border-red-900/40 p-3">
                            <p class="text-xs font-semibold text-red-700 dark:text-red-400 mb-1.5 flex items-center gap-1">
                                <Trash2 class="h-3.5 w-3.5" /> Deleted
                            </p>
                            <ul class="space-y-1">
                                <li v-for="item in RESET_DELETED" :key="item" class="text-xs text-muted-foreground flex items-center gap-1.5">
                                    <span class="h-1.5 w-1.5 rounded-full bg-red-500 shrink-0"></span>{{ item }}
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- ── Factory Reset ────────────────────────────────────────── -->
                <div class="p-5 space-y-4">
                    <div class="flex flex-col sm:flex-row sm:items-start gap-4 justify-between">
                        <div>
                            <h3 class="font-semibold text-sm text-red-800 dark:text-red-300 flex items-center gap-1.5">
                                <PackageX class="h-4 w-4" /> Factory Reset — Clean Setup
                            </h3>
                            <p class="text-xs text-red-700/80 dark:text-red-400/80 mt-1 max-w-md">
                                Wipes <strong>everything</strong> — all sales data, menu products, ingredients, recipes, modifiers,
                                payment tenders, employees, and suppliers. Only user accounts and role permissions are kept.
                                Use this to prepare the system for a new client installation. <strong>Completely irreversible.</strong>
                            </p>
                        </div>
                        <button
                            @click="openFactoryModal"
                            class="flex items-center gap-2 rounded-lg bg-gray-900 hover:bg-black dark:bg-red-900 dark:hover:bg-red-800 px-4 py-2 text-sm font-semibold text-white transition shrink-0"
                        >
                            <PackageX class="h-4 w-4" />
                            Factory Reset
                        </button>
                    </div>
                    <div class="grid sm:grid-cols-2 gap-3">
                        <div class="rounded-lg bg-white dark:bg-background/50 border border-green-200 dark:border-green-900/40 p-3">
                            <p class="text-xs font-semibold text-green-700 dark:text-green-400 mb-1.5 flex items-center gap-1">
                                <ShieldCheck class="h-3.5 w-3.5" /> Only these are kept
                            </p>
                            <ul class="space-y-1">
                                <li v-for="item in FACTORY_KEPT" :key="item" class="text-xs text-muted-foreground flex items-center gap-1.5">
                                    <span class="h-1.5 w-1.5 rounded-full bg-green-500 shrink-0"></span>{{ item }}
                                </li>
                            </ul>
                        </div>
                        <div class="rounded-lg bg-white dark:bg-background/50 border border-red-200 dark:border-red-900/40 p-3">
                            <p class="text-xs font-semibold text-red-700 dark:text-red-400 mb-1.5 flex items-center gap-1">
                                <Trash2 class="h-3.5 w-3.5" /> Everything else deleted
                            </p>
                            <ul class="space-y-1">
                                <li v-for="item in FACTORY_DELETED" :key="item" class="text-xs text-muted-foreground flex items-center gap-1.5">
                                    <span class="h-1.5 w-1.5 rounded-full bg-red-500 shrink-0"></span>{{ item }}
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- ── Transaction Reset Modal ───────────────────────────────────────────── -->
    <Teleport to="body">
        <div v-if="showResetModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4">
            <div class="w-full max-w-md rounded-xl bg-background shadow-2xl border border-red-200">
                <div class="flex items-center justify-between border-b border-red-200 p-4 bg-red-50 dark:bg-red-950/30 rounded-t-xl">
                    <div class="flex items-center gap-2">
                        <AlertTriangle class="h-5 w-5 text-red-600" />
                        <h2 class="font-bold text-red-700 dark:text-red-400">Confirm Transaction Reset</h2>
                    </div>
                    <button @click="showResetModal = false" class="rounded p-1 hover:bg-red-100 dark:hover:bg-red-900/30">
                        <X class="h-4 w-4 text-red-600" />
                    </button>
                </div>
                <div class="p-5 space-y-4">
                    <div class="rounded-lg bg-red-50 dark:bg-red-950/20 border border-red-200 dark:border-red-900/50 p-3 text-sm text-red-800 dark:text-red-300">
                        All orders, payments, inventory logs, financial records, and payroll entries will be permanently deleted.
                        Inventory quantities will be zeroed. Menu and setup data is kept. <strong>This cannot be undone.</strong>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold mb-2">
                            Type <span class="font-mono bg-muted px-1.5 py-0.5 rounded text-red-600">RESET</span> to confirm
                        </label>
                        <input
                            v-model="resetText"
                            type="text"
                            placeholder="RESET"
                            autocomplete="off"
                            class="w-full rounded-lg border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-red-500 border-red-200 bg-background"
                            @keyup.enter="executeReset"
                        />
                    </div>
                </div>
                <div class="flex justify-end gap-2 border-t p-4">
                    <button @click="showResetModal = false" class="rounded-lg border px-4 py-2 text-sm font-medium hover:bg-muted">Cancel</button>
                    <button
                        @click="executeReset"
                        :disabled="resetText !== 'RESET' || resetting"
                        class="flex items-center gap-2 rounded-lg bg-red-600 hover:bg-red-700 disabled:opacity-40 disabled:cursor-not-allowed px-4 py-2 text-sm font-bold text-white transition"
                    >
                        <Trash2 class="h-4 w-4" />
                        {{ resetting ? 'Resetting…' : 'Reset Transaction Data' }}
                    </button>
                </div>
            </div>
        </div>
    </Teleport>

    <!-- ── Factory Reset Modal ───────────────────────────────────────────────── -->
    <Teleport to="body">
        <div v-if="showFactoryModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4">
            <div class="w-full max-w-md rounded-xl bg-background shadow-2xl border-2 border-gray-900 dark:border-red-800">
                <div class="flex items-center justify-between border-b border-gray-800 dark:border-red-800 p-4 bg-gray-900 dark:bg-red-950/60 rounded-t-xl">
                    <div class="flex items-center gap-2">
                        <PackageX class="h-5 w-5 text-white" />
                        <h2 class="font-bold text-white">Factory Reset — Point of No Return</h2>
                    </div>
                    <button @click="showFactoryModal = false" class="rounded p-1 hover:bg-white/10">
                        <X class="h-4 w-4 text-white" />
                    </button>
                </div>
                <div class="p-5 space-y-4">
                    <div class="rounded-lg bg-gray-900 dark:bg-red-950/40 p-3 text-sm text-white">
                        <strong class="text-yellow-400">⚠ Extreme caution:</strong> This wipes the entire system clean —
                        all products, ingredients, recipes, employees, tenders, and all transaction history.
                        Only user login accounts are preserved.
                        <strong>This is permanent and cannot be undone.</strong>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold mb-2">
                            Type <span class="font-mono bg-muted px-1.5 py-0.5 rounded text-red-600">FACTORY RESET</span> to confirm
                        </label>
                        <input
                            v-model="factoryText"
                            type="text"
                            placeholder="FACTORY RESET"
                            autocomplete="off"
                            class="w-full rounded-lg border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-gray-900 border-gray-300 bg-background"
                            @keyup.enter="executeFactoryReset"
                        />
                    </div>
                </div>
                <div class="flex justify-end gap-2 border-t p-4">
                    <button @click="showFactoryModal = false" class="rounded-lg border px-4 py-2 text-sm font-medium hover:bg-muted">Cancel</button>
                    <button
                        @click="executeFactoryReset"
                        :disabled="factoryText !== 'FACTORY RESET' || factoryResetting"
                        class="flex items-center gap-2 rounded-lg bg-gray-900 hover:bg-black dark:bg-red-900 dark:hover:bg-red-800 disabled:opacity-40 disabled:cursor-not-allowed px-4 py-2 text-sm font-bold text-white transition"
                    >
                        <PackageX class="h-4 w-4" />
                        {{ factoryResetting ? 'Wiping…' : 'Factory Reset — Wipe Everything' }}
                    </button>
                </div>
            </div>
        </div>
    </Teleport>
</template>
