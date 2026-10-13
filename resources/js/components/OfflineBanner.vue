<script setup lang="ts">
import { WifiOff, RefreshCw, Loader2 } from 'lucide-vue-next'
import { useOfflineSync } from '@/composables/useOfflineSync'

const { isOnline, pendingCount, syncing, doSync } = useOfflineSync()
</script>

<template>
    <Transition name="banner">
        <div
            v-if="!isOnline || pendingCount > 0"
            :class="[
                'flex items-center gap-3 px-4 py-2.5 text-sm font-medium',
                !isOnline ? 'bg-red-600 text-white' : 'bg-amber-500 text-white',
            ]"
        >
            <component
                :is="!isOnline ? WifiOff : syncing ? Loader2 : RefreshCw"
                :class="['h-4 w-4 shrink-0', syncing && 'animate-spin']"
            />

            <span v-if="!isOnline">
                You are offline — orders will be saved locally and synced when connection is restored.
                <template v-if="pendingCount > 0">
                    <strong>&nbsp;({{ pendingCount }} pending)</strong>
                </template>
            </span>

            <span v-else-if="syncing">
                Syncing {{ pendingCount }} pending transaction(s)…
            </span>

            <span v-else>
                Back online —
                <strong>{{ pendingCount }} transaction(s)</strong> pending sync.
                <button @click="doSync" class="underline hover:no-underline ml-1">Sync now</button>
            </span>
        </div>
    </Transition>
</template>

<style scoped>
.banner-enter-active, .banner-leave-active { transition: all 0.25s ease; }
.banner-enter-from, .banner-leave-to { opacity: 0; transform: translateY(-100%); }
</style>
