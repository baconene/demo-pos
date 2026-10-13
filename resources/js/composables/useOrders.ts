import { ref, computed } from 'vue'
import api from '@/utils/api'

export function useOrders() {
    const orders = ref<any[]>([])
    const loading = ref(false)
    const error = ref<string | null>(null)

    const fetchOrders = async () => {
        loading.value = true
        error.value = null
        try {
            const response = await api.get('/api/v1/orders')
            orders.value = response.data.data ?? response.data
        } catch (err: any) {
            error.value = err.message
        } finally {
            loading.value = false
        }
    }

    const createOrder = async (orderData: Record<string, any>) => {
        const response = await api.post('/api/v1/orders', orderData)
        const order = response.data.data ?? response.data
        orders.value.unshift(order)
        return order
    }

    const updateOrderStatus = async (orderId: number, status: string) => {
        const response = await api.patch(`/api/v1/orders/${orderId}/status`, { status })
        const updated = response.data.data ?? response.data
        const index = orders.value.findIndex((o) => o.id === orderId)
        if (index !== -1) orders.value[index] = updated
        return updated
    }

    return {
        orders: computed(() => orders.value),
        loading: computed(() => loading.value),
        error: computed(() => error.value),
        fetchOrders,
        createOrder,
        updateOrderStatus,
    }
}

export function useActiveOrders() {
    const orders = ref<any[]>([])
    const loading = ref(false)

    const fetchActiveOrders = async () => {
        loading.value = true
        try {
            const response = await api.get('/api/v1/orders/active')
            orders.value = response.data.data ?? response.data
        } finally {
            loading.value = false
        }
    }

    return {
        orders: computed(() => orders.value),
        loading: computed(() => loading.value),
        fetchActiveOrders,
    }
}
