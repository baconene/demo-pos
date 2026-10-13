import { defineStore } from 'pinia'
import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'

export const useAuthStore = defineStore('auth', () => {
    const page = usePage()

    const user = computed(() => page.props.auth?.user ?? null)
    const roles = computed<string[]>(() => page.props.auth?.roles ?? [])
    const permissions = computed<string[]>(() => page.props.auth?.permissions ?? [])
    const isAuthenticated = computed(() => !!user.value)

    const hasRole = (role: string | string[]) => {
        const roleList = Array.isArray(role) ? role : [role]
        return roleList.some((r) => roles.value.includes(r))
    }

    const hasPermission = (permission: string) => {
        return permissions.value.includes(permission)
    }

    return {
        user,
        roles,
        permissions,
        isAuthenticated,
        hasRole,
        hasPermission,
    }
})
