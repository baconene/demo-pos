import { useOnline } from '@vueuse/core'
import { pendingCount, syncing, refreshCount, doSync } from '@/utils/offlineSync'

export function useOfflineSync() {
    const isOnline = useOnline()
    return { isOnline, pendingCount, syncing, refreshCount, doSync }
}
