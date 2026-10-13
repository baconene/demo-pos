<script setup lang="ts">
import { ref, onMounted, onBeforeUnmount, computed, markRaw } from 'vue'
import { Head } from '@inertiajs/vue3'
import gsap from 'gsap'
import {
    Globe, Radio, Smartphone, Printer, ArrowRight, ChevronLeft, ChevronRight,
    Wifi, Bluetooth, Receipt, Play, RotateCcw,
} from 'lucide-vue-next'

defineOptions({ layout: null })

// ── Slides definition ──────────────────────────────────────────────────────────
interface Slide {
    id: string
    kicker: string
    title: string
    body: string
}

const slides: Slide[] = [
    {
        id: 'intro',
        kicker: 'Architecture',
        title: 'How BypassGrill Prints a Receipt',
        body: 'A receipt travels from the browser POS to a thermal printer in under a second — without any USB cable or local driver. Here is the journey, step by step.',
    },
    {
        id: 'webapp',
        kicker: 'Step 1 · Origin',
        title: 'The Web App',
        body: 'A cashier taps “Print Receipt”. The Laravel + Vue web app builds the full receipt payload — store details, items, totals, payment and a QR code — then hands it off to Pusher Channels.',
    },
    {
        id: 'websocket',
        kicker: 'Step 2 · Transport',
        title: 'The WebSocket (Pusher Channels)',
        body: 'Pusher relays the “print” event in real time over a persistent WebSocket to every device subscribed to the “orders” channel. No polling, no IP addresses, works across any network.',
    },
    {
        id: 'android',
        kicker: 'Step 3 · Bridge',
        title: 'The Printing App (Android)',
        body: 'A lightweight Android service stays subscribed to the channel. When the event arrives it parses the receipt JSON and converts it into ESC/POS commands the printer understands.',
    },
    {
        id: 'printer',
        kicker: 'Step 4 · Output',
        title: 'The Thermal Printer',
        body: 'Over Bluetooth, the Android app streams the ESC/POS bytes to the thermal printer. The paper rolls out — order number bold at the top, QR code at the bottom. Done.',
    },
    {
        id: 'flow',
        kicker: 'End to End',
        title: 'The Full Pipeline',
        body: 'Web App → WebSocket → Android Bridge → Printer. Watch a single print job flow through all four stages in real time.',
    },
]

const current = ref(0)
const isLast = computed(() => current.value === slides.length - 1)
const isFirst = computed(() => current.value === 0)

// Which architecture nodes are highlighted on each slide (by index 0-3)
const activeNode = computed(() => {
    switch (slides[current.value].id) {
        case 'webapp':   return 0
        case 'websocket':return 1
        case 'android':  return 2
        case 'printer':  return 3
        default:         return -1
    }
})

const nodes = [
    { label: 'Web App',      sub: 'Laravel + Vue POS',  icon: markRaw(Globe),      color: '#3b82f6' },
    { label: 'WebSocket',    sub: 'Pusher Channels',    icon: markRaw(Radio),      color: '#8b5cf6' },
    { label: 'Printing App', sub: 'Android Service',    icon: markRaw(Smartphone), color: '#10b981' },
    { label: 'Printer',      sub: 'Bluetooth Thermal',  icon: markRaw(Printer),    color: '#f59e0b' },
]

// ── GSAP refs ───────────────────────────────────────────────────────────────────
const kickerEl = ref<HTMLElement | null>(null)
const titleEl = ref<HTMLElement | null>(null)
const bodyEl = ref<HTMLElement | null>(null)
const stageEl = ref<HTMLElement | null>(null)
const packetEl = ref<HTMLElement | null>(null)

let playing = ref(false)

const animateTextIn = () => {
    gsap.fromTo(
        [kickerEl.value, titleEl.value, bodyEl.value],
        { y: 24, opacity: 0 },
        { y: 0, opacity: 1, duration: 0.6, stagger: 0.1, ease: 'power3.out' },
    )
}

const pulseActiveNode = () => {
    const idx = activeNode.value
    if (idx < 0 || !stageEl.value) return
    const card = stageEl.value.querySelector(`[data-node="${idx}"]`)
    if (card) {
        gsap.fromTo(card,
            { scale: 1 },
            { scale: 1.12, duration: 0.4, yoyo: true, repeat: 1, ease: 'power2.inOut' },
        )
        gsap.to(card, { boxShadow: `0 0 0 3px ${nodes[idx].color}`, duration: 0.3 })
    }
}

const goTo = (i: number) => {
    if (i < 0 || i >= slides.length) return
    current.value = i
    // Reset node highlight rings
    if (stageEl.value) {
        stageEl.value.querySelectorAll('[data-node]').forEach((el) =>
            gsap.set(el, { boxShadow: '0 0 0 0px rgba(0,0,0,0)' }))
    }
    requestAnimationFrame(() => {
        animateTextIn()
        setTimeout(pulseActiveNode, 250)
    })
}

const next = () => { if (!isLast.value) goTo(current.value + 1) }
const prev = () => { if (!isFirst.value) goTo(current.value - 1) }

// ── End-to-end packet animation (flow slide) ────────────────────────────────────
const playFlow = () => {
    if (!packetEl.value || !stageEl.value || playing.value) return
    playing.value = true

    const cards = Array.from(stageEl.value.querySelectorAll('[data-node]')) as HTMLElement[]
    if (cards.length < 4) { playing.value = false; return }

    const stageRect = stageEl.value.getBoundingClientRect()
    const positions = cards.map((c) => {
        const r = c.getBoundingClientRect()
        return { x: r.left - stageRect.left + r.width / 2, y: r.top - stageRect.top + r.height / 2 }
    })

    const tl = gsap.timeline({ onComplete: () => { playing.value = false } })

    // Start packet at first node
    gsap.set(packetEl.value, { x: positions[0].x, y: positions[0].y, scale: 0, opacity: 1 })
    tl.to(packetEl.value, { scale: 1, duration: 0.3, ease: 'back.out(2)' })

    positions.forEach((pos, i) => {
        // Pulse the node as the packet arrives
        tl.add(() => {
            gsap.fromTo(cards[i],
                { scale: 1 },
                { scale: 1.15, duration: 0.3, yoyo: true, repeat: 1, ease: 'power2.inOut' })
            gsap.fromTo(cards[i],
                { boxShadow: '0 0 0 0px rgba(0,0,0,0)' },
                { boxShadow: `0 0 0 3px ${nodes[i].color}`, duration: 0.3, yoyo: true, repeat: 1 })
        }, i === 0 ? '>' : '<')

        if (i < positions.length - 1) {
            tl.to(packetEl.value, {
                x: positions[i + 1].x,
                y: positions[i + 1].y,
                duration: 0.8,
                ease: 'power1.inOut',
            }, '>')
        }
    })

    // Receipt pops out at the printer
    tl.to(packetEl.value, { scale: 1.4, duration: 0.2, yoyo: true, repeat: 1 })
    tl.to(packetEl.value, { opacity: 0, scale: 0.5, duration: 0.4 }, '>')
}

// ── Keyboard nav ────────────────────────────────────────────────────────────────
const onKey = (e: KeyboardEvent) => {
    if (e.key === 'ArrowRight' || e.key === ' ') { e.preventDefault(); next() }
    if (e.key === 'ArrowLeft') prev()
}

onMounted(() => {
    window.addEventListener('keydown', onKey)
    // Intro entrance — clearProps ensures elements never get stuck hidden
    gsap.from('.arch-node', { y: 30, opacity: 0, duration: 0.7, stagger: 0.12, ease: 'power3.out', delay: 0.2, clearProps: 'opacity,transform' })
    gsap.from('.arch-connector', { opacity: 0, duration: 0.5, stagger: 0.12, ease: 'power2.out', delay: 0.5, clearProps: 'opacity,transform' })
    animateTextIn()
})

onBeforeUnmount(() => window.removeEventListener('keydown', onKey))
</script>

<template>
    <Head title="Printing Architecture — BypassGrill" />

    <div class="min-h-screen bg-gradient-to-br from-slate-950 via-slate-900 to-slate-950 text-white flex flex-col overflow-hidden">

        <!-- Top bar -->
        <header class="flex items-center justify-between px-5 sm:px-8 py-4 border-b border-white/10 shrink-0">
            <div class="flex items-center gap-2">
                <Receipt class="h-5 w-5 text-amber-400" />
                <span class="font-black tracking-tight">BypassGrill</span>
                <span class="text-white/40 text-sm hidden sm:inline">· Printing Architecture</span>
            </div>
            <div class="text-xs text-white/40 font-mono">{{ current + 1 }} / {{ slides.length }}</div>
        </header>

        <!-- Main slide area -->
        <main class="flex-1 flex flex-col px-5 sm:px-8 py-6 sm:py-10 max-w-6xl mx-auto w-full">

            <!-- Text block -->
            <div class="mb-8 sm:mb-10">
                <p ref="kickerEl" class="text-amber-400 font-bold uppercase tracking-[0.2em] text-xs sm:text-sm mb-3">
                    {{ slides[current].kicker }}
                </p>
                <h1 ref="titleEl" class="text-3xl sm:text-5xl font-black tracking-tight mb-4 leading-tight">
                    {{ slides[current].title }}
                </h1>
                <p ref="bodyEl" class="text-white/60 text-base sm:text-lg max-w-2xl leading-relaxed">
                    {{ slides[current].body }}
                </p>
            </div>

            <!-- Architecture stage -->
            <div ref="stageEl" class="relative flex-1 flex items-center justify-center min-h-[260px]">

                <!-- Flowing packet (animated on flow slide) -->
                <div ref="packetEl"
                    class="absolute top-0 left-0 z-20 pointer-events-none opacity-0 -translate-x-1/2 -translate-y-1/2">
                    <div class="flex items-center justify-center h-11 w-11 rounded-xl bg-amber-400 text-slate-900 shadow-lg shadow-amber-400/40">
                        <Receipt class="h-6 w-6" />
                    </div>
                </div>

                <!-- Nodes row -->
                <div class="flex items-center justify-center gap-1 sm:gap-3 flex-wrap w-full">
                    <template v-for="(node, i) in nodes" :key="node.label">
                        <!-- Node card -->
                        <div
                            :data-node="i"
                            :class="[
                                'arch-node relative flex flex-col items-center gap-3 rounded-2xl border p-4 sm:p-6 transition-all duration-300 w-[140px] sm:w-[170px]',
                                activeNode === i
                                    ? 'border-white/40 bg-white/10 scale-105'
                                    : 'border-white/10 bg-white/5',
                            ]"
                        >
                            <div class="flex h-12 w-12 sm:h-14 sm:w-14 items-center justify-center rounded-xl"
                                :style="{ backgroundColor: node.color + '22', color: node.color }">
                                <component :is="node.icon" class="h-6 w-6 sm:h-7 sm:w-7" />
                            </div>
                            <div class="text-center">
                                <p class="font-bold text-sm sm:text-base">{{ node.label }}</p>
                                <p class="text-white/40 text-[10px] sm:text-xs mt-0.5">{{ node.sub }}</p>
                            </div>

                            <!-- Transport icon badge -->
                            <div v-if="i === 1" class="absolute -top-2 -right-2 rounded-full bg-violet-500 p-1.5">
                                <Wifi class="h-3 w-3" />
                            </div>
                            <div v-if="i === 3" class="absolute -top-2 -right-2 rounded-full bg-amber-500 p-1.5">
                                <Bluetooth class="h-3 w-3" />
                            </div>
                        </div>

                        <!-- Connector arrow -->
                        <div v-if="i < nodes.length - 1"
                            class="arch-connector flex items-center text-white/30 shrink-0">
                            <ArrowRight class="h-5 w-5 sm:h-7 sm:w-7" />
                        </div>
                    </template>
                </div>
            </div>

            <!-- Flow slide CTA -->
            <div v-if="slides[current].id === 'flow'" class="flex justify-center mt-6">
                <button @click="playFlow" :disabled="playing"
                    class="flex items-center gap-2 rounded-xl bg-amber-400 text-slate-900 px-6 py-3 font-bold hover:bg-amber-300 disabled:opacity-50 transition-colors">
                    <component :is="playing ? RotateCcw : Play" :class="['h-5 w-5', playing && 'animate-spin']" />
                    {{ playing ? 'Printing…' : 'Play the Flow' }}
                </button>
            </div>
        </main>

        <!-- Bottom controls -->
        <footer class="shrink-0 px-5 sm:px-8 py-5 border-t border-white/10">
            <div class="max-w-6xl mx-auto flex items-center justify-between gap-4">
                <button @click="prev" :disabled="isFirst"
                    class="flex items-center gap-1.5 rounded-lg border border-white/15 px-4 py-2 text-sm font-semibold hover:bg-white/5 disabled:opacity-30 transition-colors">
                    <ChevronLeft class="h-4 w-4" /> Back
                </button>

                <!-- Progress dots -->
                <div class="flex items-center gap-2">
                    <button v-for="(s, i) in slides" :key="s.id" @click="goTo(i)"
                        :class="[
                            'h-2 rounded-full transition-all duration-300',
                            i === current ? 'w-8 bg-amber-400' : 'w-2 bg-white/20 hover:bg-white/40',
                        ]"
                        :aria-label="`Go to slide ${i + 1}`" />
                </div>

                <button @click="next" :disabled="isLast"
                    class="flex items-center gap-1.5 rounded-lg bg-white/10 px-4 py-2 text-sm font-semibold hover:bg-white/20 disabled:opacity-30 transition-colors">
                    Next <ChevronRight class="h-4 w-4" />
                </button>
            </div>
        </footer>
    </div>
</template>
