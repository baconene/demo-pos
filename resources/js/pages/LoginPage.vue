<script setup lang="ts">
// Redirect to the Fortify-handled login page on mount
import { onMounted } from 'vue'
import { router } from '@inertiajs/vue3'

onMounted(() => {
    router.visit('/login')
})
</script>

<template>
    <div class="flex min-h-screen items-center justify-center">
        <p class="text-muted-foreground text-sm">Redirecting to login…</p>
    </div>
</template>
