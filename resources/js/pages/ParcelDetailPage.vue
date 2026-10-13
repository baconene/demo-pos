<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { Head, router, usePage } from '@inertiajs/vue3'
import { toast } from 'vue-sonner'
import api from '@/utils/api'
import {
    Archive, Plus, Pencil, Trash2, ArrowLeft, X,
    Package, PackageOpen, PackageCheck,
} from 'lucide-vue-next'

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: '/dashboard' },
            { title: 'Parcels', href: '/parcels' },
            { title: 'Detail' },
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

// ── Props ──────────────────────────────────────────────────────────────────────
const props = defineProps<{ parcelId: number }>()

// ── Roles ──────────────────────────────────────────────────────────────────────
const page = usePage()
const roles = computed(() => (page.props.auth as any)?.roles ?? [])
const canManage = computed(() => roles.value.includes('admin') || roles.value.includes('auditor'))

// ── State ──────────────────────────────────────────────────────────────────────
const loading  = ref(false)
const parcel   = ref<Parcel | null>(null)
const toggling = ref<Set<number>>(new Set())
const deleting = ref<Set<number>>(new Set())

// Parcel edit modal
const showParcelModal = ref(false)
const parcelSaving    = ref(false)
const parcelForm      = ref({ name: '', assigned_personnel: '', notes: '' })

// Item modal
const showItemModal = ref(false)
const itemSaving    = ref(false)
const editingItem   = ref<ParcelItem | null>(null)
const itemForm      = ref({ item_name: '', quantity: '1', status: 'in' as 'in' | 'out' })

// ── Helpers ────────────────────────────────────────────────────────────────────
const statusLabel = (s: string) => ({ in: 'IN', out: 'OUT', complete: 'COMPLETE' }[s] ?? s.toUpperCase())

const lidBgClass = (s: string) => ({
    in:       'bg-blue-500',
    out:      'bg-orange-500',
    complete: 'bg-green-500',
}[s] ?? 'bg-gray-400')

const statusBadgeClass = (s: string) => ({
    in:       'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
    out:      'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400',
    complete: 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
}[s] ?? 'bg-muted text-muted-foreground')

const statusIcon = (s: string) => ({
    in:       Package,
    out:      PackageOpen,
    complete: PackageCheck,
}[s] ?? Package)

const fmtDateTime = (s: string | null) => {
    if (!s) return ''
    return new Date(s).toLocaleString('en-PH', {
        month: 'short', day: 'numeric',
        hour: '2-digit', minute: '2-digit', hour12: true,
    })
}

// ── Load ───────────────────────────────────────────────────────────────────────
const load = async () => {
    loading.value = true
    try {
        const res = await api.get(`/api/v1/parcels/${props.parcelId}`)
        parcel.value = res.data.data
    } catch {
        toast.error('Failed to load parcel.')
    } finally {
        loading.value = false
    }
}

// ── Item actions ───────────────────────────────────────────────────────────────
const toggleItem = async (item: ParcelItem) => {
    if (toggling.value.has(item.id)) return
    toggling.value.add(item.id)
    try {
        const res = await api.patch(`/api/v1/parcels/${props.parcelId}/items/${item.id}/toggle`)
        parcel.value = res.data.data
    } catch {
        toast.error('Failed to update item status.')
    } finally {
        toggling.value.delete(item.id)
    }
}

const deleteItem = async (item: ParcelItem) => {
    if (!confirm(`Remove "${item.item_name}" from this parcel?`)) return
    deleting.value.add(item.id)
    try {
        const res = await api.delete(`/api/v1/parcels/${props.parcelId}/items/${item.id}`)
        parcel.value = res.data.data
        toast.success('Item removed.')
    } catch (err: any) {
        toast.error(err.response?.data?.message ?? 'Failed to remove item.')
    } finally {
        deleting.value.delete(item.id)
    }
}

const openAddItem = () => {
    editingItem.value = null
    itemForm.value = { item_name: '', quantity: '1', status: 'in' }
    showItemModal.value = true
}

const openEditItem = (item: ParcelItem) => {
    editingItem.value = item
    itemForm.value = { item_name: item.item_name, quantity: String(item.quantity), status: item.status }
    showItemModal.value = true
}

const saveItem = async () => {
    if (!itemForm.value.item_name.trim() || !itemForm.value.quantity) return
    itemSaving.value = true
    try {
        if (editingItem.value) {
            const res = await api.put(`/api/v1/parcels/${props.parcelId}/items/${editingItem.value.id}`, {
                item_name: itemForm.value.item_name,
                quantity: parseInt(itemForm.value.quantity),
            })
            parcel.value = res.data.data
            toast.success('Item updated.')
        } else {
            const res = await api.post(`/api/v1/parcels/${props.parcelId}/items`, {
                item_name: itemForm.value.item_name,
                quantity: parseInt(itemForm.value.quantity),
                status: itemForm.value.status,
            })
            parcel.value = res.data.data
            toast.success('Item added.')
        }
        showItemModal.value = false
    } catch (err: any) {
        toast.error(err.response?.data?.message ?? 'Failed to save item.')
    } finally {
        itemSaving.value = false
    }
}

// ── Parcel actions ─────────────────────────────────────────────────────────────
const openEditParcel = () => {
    if (!parcel.value) return
    parcelForm.value = {
        name: parcel.value.name,
        assigned_personnel: parcel.value.assigned_personnel ?? '',
        notes: parcel.value.notes ?? '',
    }
    showParcelModal.value = true
}

const saveParcel = async () => {
    if (!parcelForm.value.name.trim() || !parcel.value) return
    parcelSaving.value = true
    try {
        const res = await api.put(`/api/v1/parcels/${props.parcelId}`, {
            name: parcelForm.value.name,
            assigned_personnel: parcelForm.value.assigned_personnel || null,
            notes: parcelForm.value.notes || null,
        })
        parcel.value = res.data.data
        showParcelModal.value = false
        toast.success('Parcel updated.')
    } catch (err: any) {
        toast.error(err.response?.data?.message ?? 'Failed to save parcel.')
    } finally {
        parcelSaving.value = false
    }
}

const deleteParcel = async () => {
    if (!parcel.value) return
    if (!confirm(`Delete parcel "${parcel.value.name}"?\nThis will also delete all items inside.`)) return
    try {
        await api.delete(`/api/v1/parcels/${props.parcelId}`)
        toast.success('Parcel deleted.')
        router.visit('/parcels')
    } catch (err: any) {
        toast.error(err.response?.data?.message ?? 'Failed to delete parcel.')
    }
}

onMounted(load)
</script>

<template>
    <Head :title="parcel?.name ?? 'Parcel Detail'" />

    <div class="p-4 md:p-6 max-w-2xl mx-auto space-y-5">

        <!-- ── Back button ─────────────────────────────────────────────────── -->
        <button @click="router.visit('/parcels')"
            class="flex items-center gap-1.5 text-sm text-muted-foreground hover:text-foreground transition-colors">
            <ArrowLeft class="h-4 w-4" /> Back to Parcels
        </button>

        <!-- ── Loading ────────────────────────────────────────────────────── -->
        <div v-if="loading" class="flex justify-center py-16">
            <div class="h-6 w-6 animate-spin rounded-full border-2 border-primary border-t-transparent" />
        </div>

        <template v-else-if="parcel">

            <!-- ── Parcel header card ──────────────────────────────────────── -->
            <div class="rounded-2xl border bg-card overflow-hidden shadow-sm">

                <!-- Colored lid -->
                <div class="px-5 py-4 flex items-center gap-3" :class="lidBgClass(parcel.status)">
                    <component :is="statusIcon(parcel.status)" class="h-6 w-6 text-white shrink-0" />
                    <div class="flex-1 min-w-0">
                        <p class="text-[11px] font-mono font-bold text-white/75">{{ parcel.parcel_number }}</p>
                        <p class="text-white font-black text-lg leading-tight truncate">{{ parcel.name }}</p>
                    </div>
                    <span class="text-sm font-black text-white bg-white/20 px-3 py-1 rounded-full shrink-0">
                        {{ statusLabel(parcel.status) }}
                    </span>
                </div>

                <!-- Info body -->
                <div class="p-5">
                    <div class="grid grid-cols-2 gap-x-6 gap-y-3 text-sm">
                        <div v-if="parcel.assigned_personnel">
                            <p class="text-[11px] font-semibold text-muted-foreground uppercase tracking-wide mb-0.5">Assigned To</p>
                            <p class="font-semibold">{{ parcel.assigned_personnel }}</p>
                        </div>
                        <div>
                            <p class="text-[11px] font-semibold text-muted-foreground uppercase tracking-wide mb-0.5">Items</p>
                            <p class="font-semibold">{{ parcel.items_in }} deployed / {{ parcel.items_count }} total</p>
                        </div>
                    </div>

                    <p v-if="parcel.notes" class="text-sm text-muted-foreground mt-4 p-3 bg-muted/50 rounded-xl">
                        {{ parcel.notes }}
                    </p>

                    <!-- Progress bar -->
                    <div v-if="parcel.items_count > 0" class="mt-4">
                        <div class="flex items-center justify-between text-xs text-muted-foreground mb-1.5">
                            <span>Deployment progress</span>
                            <span>{{ parcel.status === 'complete' ? 100 : Math.round((parcel.items_in / parcel.items_count) * 100) }}%</span>
                        </div>
                        <div class="h-2 rounded-full bg-muted overflow-hidden">
                            <div class="h-full rounded-full transition-all duration-500"
                                :class="parcel.status === 'complete' ? 'bg-green-500' : 'bg-blue-500'"
                                :style="`width:${parcel.status === 'complete' ? 100 : Math.round((parcel.items_in / parcel.items_count) * 100)}%`" />
                        </div>
                    </div>

                    <!-- Admin actions -->
                    <div v-if="canManage" class="flex gap-2 mt-5 pt-4 border-t">
                        <button @click="openEditParcel"
                            class="flex items-center gap-1.5 rounded-lg border px-3 py-2 text-sm font-medium hover:bg-muted transition-colors">
                            <Pencil class="h-3.5 w-3.5" /> Edit Parcel
                        </button>
                        <button @click="deleteParcel"
                            class="flex items-center gap-1.5 rounded-lg border border-red-200 dark:border-red-900 text-red-600 px-3 py-2 text-sm font-medium hover:bg-red-50 dark:hover:bg-red-950/20 transition-colors">
                            <Trash2 class="h-3.5 w-3.5" /> Delete Parcel
                        </button>
                    </div>
                </div>
            </div>

            <!-- ── Items section ───────────────────────────────────────────── -->
            <div class="rounded-2xl border bg-card overflow-hidden shadow-sm">

                <!-- Section header -->
                <div class="flex items-center justify-between px-5 py-4 border-b">
                    <h2 class="font-bold">Parcel Contents</h2>
                    <button v-if="canManage" @click="openAddItem"
                        class="flex items-center gap-1.5 rounded-lg bg-primary px-3 py-1.5 text-xs font-bold text-primary-foreground hover:bg-primary/90 transition-colors">
                        <Plus class="h-3.5 w-3.5" /> Add Item
                    </button>
                </div>

                <!-- Items list -->
                <div v-if="parcel.items.length > 0" class="divide-y">
                    <div v-for="item in parcel.items" :key="item.id"
                        :class="['flex items-center gap-3 px-5 py-4 transition-colors',
                            item.status === 'in' ? 'bg-blue-50/50 dark:bg-blue-950/10' : '']">

                        <!-- Status dot -->
                        <span :class="['w-2.5 h-2.5 rounded-full shrink-0 mt-0.5',
                            item.status === 'in' ? 'bg-blue-500' : 'bg-muted-foreground/25']" />

                        <!-- Item info -->
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-semibold leading-tight"
                                :class="item.status !== 'in' ? 'text-muted-foreground' : ''">
                                {{ item.item_name }}
                            </p>
                            <p class="text-[10px] text-muted-foreground mt-0.5">
                                Qty: {{ item.quantity }}
                                <span v-if="item.status_updated_at"> · {{ fmtDateTime(item.status_updated_at) }}</span>
                            </p>
                        </div>

                        <!-- Status badge -->
                        <span :class="['text-[11px] font-bold px-2.5 py-1 rounded-lg shrink-0',
                            item.status === 'in'
                                ? 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400'
                                : 'bg-muted text-muted-foreground']">
                            {{ item.status === 'in' ? 'DEPLOYED' : 'IN BOX' }}
                        </span>

                        <!-- Toggle (only the relevant action) -->
                        <button @click="toggleItem(item)" :disabled="toggling.has(item.id)"
                            :class="['shrink-0 rounded-lg px-3 py-1.5 text-[11px] font-bold transition-colors disabled:opacity-50',
                                item.status === 'in'
                                    ? 'bg-orange-100 text-orange-700 hover:bg-orange-200 dark:bg-orange-900/30 dark:text-orange-400'
                                    : 'bg-blue-100 text-blue-700 hover:bg-blue-200 dark:bg-blue-900/30 dark:text-blue-400']">
                            {{ toggling.has(item.id) ? '…' : (item.status === 'in' ? '↩ Return' : '↗ Deploy') }}
                        </button>

                        <!-- Admin: edit + delete -->
                        <div v-if="canManage" class="flex gap-0.5 shrink-0">
                            <button @click="openEditItem(item)"
                                class="p-1.5 rounded text-muted-foreground hover:text-foreground hover:bg-muted transition-colors">
                                <Pencil class="h-3.5 w-3.5" />
                            </button>
                            <button @click="deleteItem(item)" :disabled="deleting.has(item.id)"
                                class="p-1.5 rounded text-muted-foreground hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-950/20 transition-colors disabled:opacity-50">
                                <Trash2 class="h-3.5 w-3.5" />
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Empty items -->
                <div v-else class="px-5 py-12 text-center">
                    <Archive class="h-10 w-10 text-muted-foreground/30 mx-auto mb-3" />
                    <p class="text-sm text-muted-foreground">No items in this parcel yet.</p>
                    <p v-if="canManage" class="text-xs text-muted-foreground mt-1">
                        Use "Add Item" to track contents.
                    </p>
                </div>
            </div>

        </template>

        <!-- ── Not found ──────────────────────────────────────────────────── -->
        <div v-else-if="!loading" class="rounded-xl border bg-card p-12 text-center">
            <Archive class="h-12 w-12 text-muted-foreground/30 mx-auto mb-3" />
            <p class="font-semibold text-muted-foreground">Parcel not found.</p>
        </div>

        <!-- ── Edit Parcel Modal ───────────────────────────────────────────── -->
        <Teleport to="body">
            <div v-if="showParcelModal"
                class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm"
                @click.self="showParcelModal = false">
                <div class="w-full max-w-md rounded-2xl border bg-card shadow-2xl">
                    <div class="flex items-center justify-between px-5 pt-5 pb-4 border-b">
                        <h2 class="font-bold text-base">Edit Parcel</h2>
                        <button @click="showParcelModal = false" class="text-muted-foreground hover:text-foreground">
                            <X class="h-4 w-4" />
                        </button>
                    </div>
                    <div class="p-5 space-y-4">
                        <div>
                            <label class="text-xs font-medium text-muted-foreground block mb-1.5">Name *</label>
                            <input v-model="parcelForm.name" type="text"
                                class="w-full rounded-lg border bg-background px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary"
                                @keydown.enter="saveParcel" />
                        </div>
                        <div>
                            <label class="text-xs font-medium text-muted-foreground block mb-1.5">Assigned Personnel</label>
                            <input v-model="parcelForm.assigned_personnel" type="text" placeholder="Staff name"
                                class="w-full rounded-lg border bg-background px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary" />
                        </div>
                        <div>
                            <label class="text-xs font-medium text-muted-foreground block mb-1.5">Notes</label>
                            <textarea v-model="parcelForm.notes" rows="2"
                                class="w-full rounded-lg border bg-background px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary resize-none" />
                        </div>
                    </div>
                    <div class="flex gap-2 px-5 pb-5">
                        <button @click="saveParcel" :disabled="parcelSaving || !parcelForm.name.trim()"
                            class="flex-1 rounded-lg bg-primary py-2.5 text-sm font-bold text-primary-foreground hover:bg-primary/90 disabled:opacity-50 transition-colors">
                            {{ parcelSaving ? 'Saving…' : 'Save Changes' }}
                        </button>
                        <button @click="showParcelModal = false"
                            class="rounded-lg border px-4 py-2.5 text-sm font-medium hover:bg-muted transition-colors">
                            Cancel
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- ── Item Modal ──────────────────────────────────────────────────── -->
        <Teleport to="body">
            <div v-if="showItemModal"
                class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm"
                @click.self="showItemModal = false">
                <div class="w-full max-w-sm rounded-2xl border bg-card shadow-2xl">
                    <div class="flex items-center justify-between px-5 pt-5 pb-4 border-b">
                        <h2 class="font-bold text-base">{{ editingItem ? 'Edit Item' : 'Add Item' }}</h2>
                        <button @click="showItemModal = false" class="text-muted-foreground hover:text-foreground">
                            <X class="h-4 w-4" />
                        </button>
                    </div>
                    <div class="p-5 space-y-4">
                        <div>
                            <label class="text-xs font-medium text-muted-foreground block mb-1.5">Item Name *</label>
                            <input v-model="itemForm.item_name" type="text" placeholder="e.g. Spoon, Tongs"
                                class="w-full rounded-lg border bg-background px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary"
                                @keydown.enter="saveItem" />
                        </div>
                        <div>
                            <label class="text-xs font-medium text-muted-foreground block mb-1.5">Quantity *</label>
                            <input v-model="itemForm.quantity" type="number" min="1"
                                class="w-full rounded-lg border bg-background px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary" />
                        </div>
                        <div v-if="!editingItem">
                            <label class="text-xs font-medium text-muted-foreground block mb-1.5">Initial Status</label>
                            <div class="grid grid-cols-2 gap-2">
                                <button @click="itemForm.status = 'in'"
                                    :class="['rounded-lg border py-2 text-xs font-bold transition-colors',
                                        itemForm.status === 'in'
                                            ? 'bg-blue-100 text-blue-700 border-blue-300 dark:bg-blue-900/30 dark:border-blue-700'
                                            : 'hover:bg-muted']">
                                    IN (Deployed)
                                </button>
                                <button @click="itemForm.status = 'out'"
                                    :class="['rounded-lg border py-2 text-xs font-bold transition-colors',
                                        itemForm.status === 'out'
                                            ? 'bg-orange-100 text-orange-700 border-orange-300 dark:bg-orange-900/30 dark:border-orange-700'
                                            : 'hover:bg-muted']">
                                    OUT (In Box)
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="flex gap-2 px-5 pb-5">
                        <button @click="saveItem" :disabled="itemSaving || !itemForm.item_name.trim() || !itemForm.quantity"
                            class="flex-1 rounded-lg bg-primary py-2.5 text-sm font-bold text-primary-foreground hover:bg-primary/90 disabled:opacity-50 transition-colors">
                            {{ itemSaving ? 'Saving…' : (editingItem ? 'Save Changes' : 'Add Item') }}
                        </button>
                        <button @click="showItemModal = false"
                            class="rounded-lg border px-4 py-2.5 text-sm font-medium hover:bg-muted transition-colors">
                            Cancel
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>

    </div>
</template>
