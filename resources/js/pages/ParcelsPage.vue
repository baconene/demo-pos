<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { Head, router, usePage } from '@inertiajs/vue3'
import { toast } from 'vue-sonner'
import api from '@/utils/api'
import {
    Archive, Plus, Pencil, Trash2, Search, X,
    Package, PackageOpen, PackageCheck, CheckCircle2,
} from 'lucide-vue-next'

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: '/dashboard' },
            { title: 'Parcels', href: '/parcels' },
        ],
    },
})

// ── Types ──────────────────────────────────────────────────────────────────────
interface ParcelItem {
    id: number
    item_name: string
    quantity: number
    status: 'in' | 'out'
    status_updated_at: string | null
}
interface Parcel {
    id: number
    parcel_number: string
    name: string
    assigned_personnel: string | null
    status: 'in' | 'out' | 'complete'
    notes: string | null
    updated_at: string | null
    items: ParcelItem[]
    items_count: number
    items_in: number
    items_out: number
}
interface Stats { total: number; in: number; out: number; complete: number }

// ── Roles ──────────────────────────────────────────────────────────────────────
const page = usePage()
const roles = computed(() => (page.props.auth as any)?.roles ?? [])
const canManage = computed(() => roles.value.includes('admin') || roles.value.includes('auditor'))

// ── State ──────────────────────────────────────────────────────────────────────
const loading = ref(false)
const parcels = ref<Parcel[]>([])
const stats   = ref<Stats>({ total: 0, in: 0, out: 0, complete: 0 })
const search  = ref('')
const statusFilter = ref('')
const deleting = ref<Set<number>>(new Set())

// Parcel modal
const showParcelModal = ref(false)
const parcelSaving    = ref(false)
const editingParcel   = ref<Parcel | null>(null)
const parcelForm      = ref({ name: '', parcel_number: '', assigned_personnel: '', notes: '' })

// ── Helpers ────────────────────────────────────────────────────────────────────
const statusLabel = (s: string) => ({ in: 'IN', out: 'OUT', complete: 'COMPLETE' }[s] ?? s.toUpperCase())

const lidBgClass = (s: string) => ({
    in:       'bg-blue-500',
    out:      'bg-orange-500',
    complete: 'bg-green-500',
}[s] ?? 'bg-gray-400')

const lidIcon = (s: string) => ({
    in:       Package,
    out:      PackageOpen,
    complete: PackageCheck,
}[s] ?? Package)

// ── Computed ───────────────────────────────────────────────────────────────────
const filtered = computed(() => {
    let list = parcels.value
    if (statusFilter.value) list = list.filter(p => p.status === statusFilter.value)
    if (search.value.trim()) {
        const q = search.value.toLowerCase()
        list = list.filter(p =>
            p.name.toLowerCase().includes(q) ||
            p.parcel_number.toLowerCase().includes(q) ||
            (p.assigned_personnel ?? '').toLowerCase().includes(q)
        )
    }
    return list
})

// ── Data loading ───────────────────────────────────────────────────────────────
const load = async () => {
    loading.value = true
    try {
        const res = await api.get('/api/v1/parcels')
        parcels.value = res.data.data ?? []
        stats.value   = res.data.stats ?? { total: 0, in: 0, out: 0, complete: 0 }
    } catch {
        toast.error('Failed to load parcels.')
    } finally {
        loading.value = false
    }
}

// ── Parcel CRUD ────────────────────────────────────────────────────────────────
const openAddParcel = () => {
    editingParcel.value = null
    parcelForm.value = { name: '', parcel_number: '', assigned_personnel: '', notes: '' }
    showParcelModal.value = true
}

const openEditParcel = (p: Parcel) => {
    editingParcel.value = p
    parcelForm.value = {
        name: p.name,
        parcel_number: p.parcel_number,
        assigned_personnel: p.assigned_personnel ?? '',
        notes: p.notes ?? '',
    }
    showParcelModal.value = true
}

const saveParcel = async () => {
    if (!parcelForm.value.name.trim()) return
    parcelSaving.value = true
    try {
        const payload = {
            name: parcelForm.value.name,
            parcel_number: parcelForm.value.parcel_number || undefined,
            assigned_personnel: parcelForm.value.assigned_personnel || null,
            notes: parcelForm.value.notes || null,
        }
        if (editingParcel.value) {
            const res = await api.put(`/api/v1/parcels/${editingParcel.value.id}`, payload)
            const idx = parcels.value.findIndex(p => p.id === editingParcel.value!.id)
            if (idx !== -1) parcels.value[idx] = res.data.data
            toast.success('Parcel updated.')
        } else {
            await api.post('/api/v1/parcels', payload)
            toast.success('Parcel created.')
            await load()
        }
        showParcelModal.value = false
    } catch (err: any) {
        toast.error(err.response?.data?.message ?? 'Failed to save parcel.')
    } finally {
        parcelSaving.value = false
    }
}

const deleteParcel = async (p: Parcel) => {
    if (!confirm(`Delete parcel "${p.name}"?\nThis will also delete all items inside.`)) return
    deleting.value.add(p.id)
    try {
        await api.delete(`/api/v1/parcels/${p.id}`)
        parcels.value = parcels.value.filter(x => x.id !== p.id)
        stats.value.total = Math.max(0, stats.value.total - 1)
        stats.value[p.status] = Math.max(0, stats.value[p.status] - 1)
        toast.success('Parcel deleted.')
    } catch (err: any) {
        toast.error(err.response?.data?.message ?? 'Failed to delete parcel.')
    } finally {
        deleting.value.delete(p.id)
    }
}

onMounted(load)
</script>

<template>
    <Head title="Parcel Tracking" />

    <div class="space-y-5 p-4 md:p-6">

        <!-- ── Header ─────────────────────────────────────────────────────── -->
        <div class="flex items-center justify-between gap-3 flex-wrap">
            <div class="flex items-center gap-2">
                <Archive class="h-5 w-5 text-primary" />
                <h1 class="text-lg font-bold">Parcel Tracking</h1>
            </div>
            <button v-if="canManage" @click="openAddParcel"
                class="flex items-center gap-1.5 rounded-lg bg-primary px-4 py-2 text-sm font-bold text-primary-foreground hover:bg-primary/90 transition-colors">
                <Plus class="h-4 w-4" /> New Parcel
            </button>
        </div>

        <!-- ── Stats cards ────────────────────────────────────────────────── -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
            <button @click="statusFilter = ''"
                :class="['rounded-xl border p-4 text-left transition-all', !statusFilter ? 'ring-2 ring-primary border-primary' : 'bg-card hover:bg-muted/30']">
                <Package class="h-5 w-5 text-muted-foreground mb-2" />
                <p class="text-2xl font-black tabular-nums">{{ stats.total }}</p>
                <p class="text-xs font-medium text-muted-foreground mt-0.5">Total Parcels</p>
            </button>

            <button @click="statusFilter = statusFilter === 'in' ? '' : 'in'"
                :class="['rounded-xl border p-4 text-left transition-all', statusFilter === 'in' ? 'ring-2 ring-blue-500 border-blue-500' : 'bg-card hover:bg-muted/30']">
                <PackageOpen class="h-5 w-5 text-blue-500 mb-2" />
                <p class="text-2xl font-black tabular-nums text-blue-600 dark:text-blue-400">{{ stats.in }}</p>
                <p class="text-xs font-medium text-muted-foreground mt-0.5">Active (IN)</p>
            </button>

            <button @click="statusFilter = statusFilter === 'out' ? '' : 'out'"
                :class="['rounded-xl border p-4 text-left transition-all', statusFilter === 'out' ? 'ring-2 ring-orange-500 border-orange-500' : 'bg-card hover:bg-muted/30']">
                <Archive class="h-5 w-5 text-orange-500 mb-2" />
                <p class="text-2xl font-black tabular-nums text-orange-600 dark:text-orange-400">{{ stats.out }}</p>
                <p class="text-xs font-medium text-muted-foreground mt-0.5">Packing (OUT)</p>
            </button>

            <button @click="statusFilter = statusFilter === 'complete' ? '' : 'complete'"
                :class="['rounded-xl border p-4 text-left transition-all', statusFilter === 'complete' ? 'ring-2 ring-green-500 border-green-500' : 'bg-card hover:bg-muted/30']">
                <CheckCircle2 class="h-5 w-5 text-green-500 mb-2" />
                <p class="text-2xl font-black tabular-nums text-green-600 dark:text-green-400">{{ stats.complete }}</p>
                <p class="text-xs font-medium text-muted-foreground mt-0.5">Complete</p>
            </button>
        </div>

        <!-- ── Search bar ─────────────────────────────────────────────────── -->
        <div class="relative">
            <Search class="absolute left-3 top-3 h-4 w-4 text-muted-foreground pointer-events-none" />
            <input v-model="search" type="text" placeholder="Search parcels by name, number or personnel…"
                class="w-full pl-10 pr-4 py-2.5 rounded-xl border bg-card text-sm focus:outline-none focus:ring-2 focus:ring-primary" />
        </div>

        <!-- ── Loading ────────────────────────────────────────────────────── -->
        <div v-if="loading" class="flex justify-center py-12">
            <div class="h-6 w-6 animate-spin rounded-full border-2 border-primary border-t-transparent" />
        </div>

        <!-- ── Empty state ────────────────────────────────────────────────── -->
        <div v-else-if="filtered.length === 0" class="rounded-xl border bg-card p-12 text-center">
            <Archive class="h-12 w-12 text-muted-foreground/30 mx-auto mb-3" />
            <p class="font-semibold text-muted-foreground">
                {{ search || statusFilter ? 'No parcels match your filter.' : 'No parcels yet.' }}
            </p>
            <p v-if="!search && !statusFilter && canManage" class="text-sm text-muted-foreground mt-1">
                Create your first parcel to start tracking items.
            </p>
        </div>

        <!-- ── Box grid ───────────────────────────────────────────────────── -->
        <div v-else class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4">
            <div v-for="parcel in filtered" :key="parcel.id"
                @click="router.visit(`/parcels/${parcel.id}`)"
                class="cursor-pointer group relative rounded-2xl border bg-card overflow-hidden shadow-sm hover:shadow-xl transition-all duration-200 hover:-translate-y-1">

                <!-- Box lid (colored by status) -->
                <div class="px-4 py-3 flex items-center gap-2" :class="lidBgClass(parcel.status)">
                    <component :is="lidIcon(parcel.status)" class="h-4 w-4 text-white shrink-0" />
                    <span class="text-[11px] font-mono font-bold text-white/90 flex-1 truncate">
                        {{ parcel.parcel_number }}
                    </span>
                    <span class="text-[10px] font-black text-white bg-white/25 px-2 py-0.5 rounded-full shrink-0">
                        {{ statusLabel(parcel.status) }}
                    </span>
                </div>

                <!-- Box body -->
                <div class="p-4">
                    <p class="font-bold text-sm leading-tight line-clamp-2 mb-1">{{ parcel.name }}</p>
                    <p class="text-xs text-muted-foreground truncate h-4">
                        {{ parcel.assigned_personnel ? `👤 ${parcel.assigned_personnel}` : '' }}
                    </p>

                    <!-- Items progress -->
                    <div class="mt-3 space-y-1.5">
                        <div class="flex items-center justify-between text-[10px] text-muted-foreground">
                            <span>{{ parcel.items_count }} item{{ parcel.items_count !== 1 ? 's' : '' }}</span>
                            <span v-if="parcel.items_count > 0">
                                {{ parcel.items_in }}/{{ parcel.items_count }} deployed
                            </span>
                        </div>
                        <div class="h-1.5 rounded-full bg-muted overflow-hidden">
                            <div class="h-full rounded-full transition-all duration-500"
                                :class="parcel.status === 'complete' ? 'bg-green-500' : 'bg-blue-500'"
                                :style="`width:${parcel.items_count > 0 ? (parcel.status === 'complete' ? 100 : Math.round((parcel.items_in / parcel.items_count) * 100)) : 0}%`" />
                        </div>
                    </div>

                    <!-- Admin actions (fade in on hover) -->
                    <div v-if="canManage"
                        class="flex gap-1 justify-end mt-3 opacity-0 group-hover:opacity-100 transition-opacity duration-150"
                        @click.stop>
                        <button @click.stop="openEditParcel(parcel)"
                            class="p-1.5 rounded-lg text-muted-foreground hover:text-foreground hover:bg-muted transition-colors"
                            title="Edit parcel">
                            <Pencil class="h-3.5 w-3.5" />
                        </button>
                        <button @click.stop="deleteParcel(parcel)" :disabled="deleting.has(parcel.id)"
                            class="p-1.5 rounded-lg text-muted-foreground hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-950/20 transition-colors disabled:opacity-50"
                            title="Delete parcel">
                            <Trash2 class="h-3.5 w-3.5" />
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- ── Parcel Modal ────────────────────────────────────────────────── -->
        <Teleport to="body">
            <div v-if="showParcelModal"
                class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm"
                @click.self="showParcelModal = false">
                <div class="w-full max-w-md rounded-2xl border bg-card shadow-2xl">
                    <div class="flex items-center justify-between px-5 pt-5 pb-4 border-b">
                        <h2 class="font-bold text-base">{{ editingParcel ? 'Edit Parcel' : 'New Parcel' }}</h2>
                        <button @click="showParcelModal = false" class="text-muted-foreground hover:text-foreground">
                            <X class="h-4 w-4" />
                        </button>
                    </div>
                    <div class="p-5 space-y-4">
                        <div>
                            <label class="text-xs font-medium text-muted-foreground block mb-1.5">Parcel Name *</label>
                            <input v-model="parcelForm.name" type="text" placeholder="e.g. Kitchen Box A"
                                class="w-full rounded-lg border bg-background px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary"
                                @keydown.enter="saveParcel" />
                        </div>
                        <div>
                            <label class="text-xs font-medium text-muted-foreground block mb-1.5">
                                Parcel Number
                                <span class="font-normal opacity-60">(auto if blank)</span>
                            </label>
                            <input v-model="parcelForm.parcel_number" type="text" placeholder="P-001"
                                class="w-full rounded-lg border bg-background px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary" />
                        </div>
                        <div>
                            <label class="text-xs font-medium text-muted-foreground block mb-1.5">Assigned Personnel</label>
                            <input v-model="parcelForm.assigned_personnel" type="text" placeholder="Staff name"
                                class="w-full rounded-lg border bg-background px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary" />
                        </div>
                        <div>
                            <label class="text-xs font-medium text-muted-foreground block mb-1.5">Notes</label>
                            <textarea v-model="parcelForm.notes" rows="2" placeholder="Optional notes…"
                                class="w-full rounded-lg border bg-background px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary resize-none" />
                        </div>
                    </div>
                    <div class="flex gap-2 px-5 pb-5">
                        <button @click="saveParcel" :disabled="parcelSaving || !parcelForm.name.trim()"
                            class="flex-1 rounded-lg bg-primary py-2.5 text-sm font-bold text-primary-foreground hover:bg-primary/90 disabled:opacity-50 transition-colors">
                            {{ parcelSaving ? 'Saving…' : (editingParcel ? 'Save Changes' : 'Create Parcel') }}
                        </button>
                        <button @click="showParcelModal = false"
                            class="rounded-lg border px-4 py-2.5 text-sm font-medium hover:bg-muted transition-colors">
                            Cancel
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>

    </div>
</template>
