<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import { ArrowDownRight, ArrowUpRight } from 'lucide-vue-next';
import { cn, num } from '@/lib/utils';
import { toneSoft } from '@/lib/tone';

/**
 * A single headline metric.
 *
 * Value first (it is what people scan for), label under it, optional trend or
 * hint last. Turns into a link when `href` is given so a KPI can jump to the
 * filtered list it summarises.
 */
const props = defineProps({
    label: { type: String, required: true },
    value: { type: [String, Number], default: 0 },
    hint: { type: String, default: '' },
    icon: { type: [Object, Function], default: null },
    tone: { type: String, default: 'primary' },
    href: { type: String, default: null },
    /** Percentage/absolute change vs. the previous period. */
    delta: { type: [String, Number], default: null },
    /** 'up' | 'down' — direction of `delta`. */
    deltaDirection: { type: String, default: null },
    /** Whether an upward delta is a good thing (flips the delta colour). */
    upIsGood: { type: Boolean, default: true },
    formatNumber: { type: Boolean, default: true },
    class: { type: [String, Array, Object], default: '' },
});

const display = computed(() =>
    props.formatNumber && typeof props.value === 'number' ? num(props.value) : props.value);

const isUp = computed(() => props.deltaDirection === 'up');
const deltaClass = computed(() =>
    (isUp.value === props.upIsGood) ? 'text-success' : 'text-destructive');
</script>

<template>
    <component :is="href ? Link : 'div'" :href="href" data-slot="card" :class="cn(
        'block rounded-lg border border-border bg-card p-4',
        href && 'transition-colors hover:border-primary/40 hover:bg-muted/40',
        props.class,
    )">
        <div class="flex items-start justify-between gap-3">
            <p class="text-2xl font-bold tabular-nums leading-none text-foreground">{{ display }}</p>
            <span v-if="icon" :class="cn('flex size-8 shrink-0 items-center justify-center rounded-md', toneSoft(tone))">
                <component :is="icon" class="size-4" aria-hidden="true" />
            </span>
        </div>

        <p class="mt-2.5 truncate text-base text-muted-foreground">{{ label }}</p>

        <p v-if="delta !== null" class="mt-1 flex items-center gap-1 text-xs text-muted-foreground">
            <span :class="cn('inline-flex items-center gap-0.5 font-semibold tabular-nums', deltaClass)">
                <component :is="isUp ? ArrowUpRight : ArrowDownRight" class="size-3" aria-hidden="true" />
                {{ delta }}
            </span>
            <span v-if="hint">{{ hint }}</span>
        </p>
        <p v-else-if="hint" class="mt-1 truncate text-xs text-muted-foreground">{{ hint }}</p>
    </component>
</template>
