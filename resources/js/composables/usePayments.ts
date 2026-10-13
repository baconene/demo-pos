import { ref, computed } from 'vue'
import api from '@/utils/api'

export function usePayments() {
  const loading = ref(false)
  const error = ref(null)

  const processPayment = async (orderId, paymentData) => {
    loading.value = true
    try {
      const response = await api.post('/api/v1/payments', {
        order_id: orderId,
        ...paymentData,
      })
      return response.data
    } catch (err) {
      error.value = err.message
      throw err
    } finally {
      loading.value = false
    }
  }

  const refundPayment = async (paymentId, refundData) => {
    loading.value = true
    try {
      const response = await api.post(`/api/v1/payments/${paymentId}/refund`, refundData)
      return response.data
    } catch (err) {
      error.value = err.message
      throw err
    } finally {
      loading.value = false
    }
  }

  return {
    loading: computed(() => loading.value),
    error: computed(() => error.value),
    processPayment,
    refundPayment,
  }
}
