<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { Store } from 'lucide-vue-next';
import { computed } from 'vue';
const page = usePage<{logoUrl?: string | null; brandName?: string | null}>();
const logoUrl = computed(() => page.props.logoUrl);
const brandName = computed(() => page.props.brandName || 'DEMO POS');
</script>
<template>
  <div class="flex aspect-square size-8 shrink-0 items-center justify-center overflow-hidden rounded-lg bg-slate-700 text-white">
    <img v-if="logoUrl" :src="logoUrl" class="size-8 object-contain" alt="" />
    <Store v-else class="size-5" aria-hidden="true" />
  </div>
  <div class="ml-1 grid min-w-0 flex-1 text-left text-sm group-data-[collapsible=icon]:hidden">
    <span class="truncate text-[13px] font-bold tracking-tight">{{ brandName }}</span>
    <span class="mt-1 text-[9px] font-medium tracking-wide opacity-65">POINT OF SALE</span>
  </div>
</template>
