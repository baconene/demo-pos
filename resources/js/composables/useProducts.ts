import { ref, computed } from 'vue'
import api from '@/utils/api'

export function useProducts() {
  const products = ref([])
  const loading = ref(false)
  const error = ref(null)

  const fetchProducts = async () => {
    loading.value = true
    try {
      const response = await api.get('/api/v1/products')
      products.value = response.data
    } catch (err) {
      error.value = err.message
    } finally {
      loading.value = false
    }
  }

  const getByCategory = async (categoryId) => {
    try {
      const response = await api.get(`/api/v1/products/category/${categoryId}`)
      return response.data
    } catch (err) {
      error.value = err.message
      throw err
    }
  }

  const search = async (query) => {
    try {
      const response = await api.get('/api/v1/products/search', { params: { q: query } })
      return response.data
    } catch (err) {
      error.value = err.message
      throw err
    }
  }

  return {
    products: computed(() => products.value),
    loading: computed(() => loading.value),
    error: computed(() => error.value),
    fetchProducts,
    getByCategory,
    search,
  }
}

export function useCategories() {
  const categories = ref([])
  const loading = ref(false)

  const fetchCategories = async () => {
    loading.value = true
    try {
      const response = await api.get('/api/v1/categories')
      categories.value = response.data
    } finally {
      loading.value = false
    }
  }

  return {
    categories: computed(() => categories.value),
    loading: computed(() => loading.value),
    fetchCategories,
  }
}
