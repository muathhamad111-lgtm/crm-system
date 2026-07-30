<script setup>
import { cn } from '@/lib/utils';

/**
 * Placeholder shown while a region is loading. `variant` shapes the skeleton to
 * match what will replace it, so the layout does not jump on arrival.
 */
const props = defineProps({
    /** 'list' | 'table' | 'cards' | 'block' */
    variant: { type: String, default: 'list' },
    rows: { type: Number, default: 5 },
    label: { type: String, default: 'جارٍ التحميل…' },
    class: { type: [String, Array, Object], default: '' },
});
</script>

<template>
    <div :class="cn('w-full', props.class)" role="status" :aria-label="label" aria-live="polite">
        <span class="sr-only">{{ label }}</span>

        <div v-if="variant === 'cards'" class="grid grid-cols-2 gap-3 md:grid-cols-4">
            <div v-for="i in rows" :key="i" class="rounded-lg border border-border bg-card p-4">
                <div class="skeleton h-6 w-16"></div>
                <div class="skeleton mt-3 h-3 w-24"></div>
            </div>
        </div>

        <div v-else-if="variant === 'table'" class="divide-y divide-border">
            <div v-for="i in rows" :key="i" class="flex items-center gap-3 px-4 py-3">
                <div class="skeleton size-8 shrink-0 rounded-full"></div>
                <div class="skeleton h-3 flex-1"></div>
                <div class="skeleton h-3 w-20 shrink-0"></div>
                <div class="skeleton h-5 w-16 shrink-0 rounded-full"></div>
            </div>
        </div>

        <div v-else-if="variant === 'block'" class="space-y-2.5">
            <div class="skeleton h-3 w-1/3"></div>
            <div class="skeleton h-3 w-full"></div>
            <div class="skeleton h-3 w-5/6"></div>
        </div>

        <div v-else class="divide-y divide-border">
            <div v-for="i in rows" :key="i" class="flex items-start gap-3 px-4 py-3">
                <div class="skeleton size-9 shrink-0 rounded-lg"></div>
                <div class="min-w-0 flex-1 space-y-2">
                    <div class="skeleton h-3 w-1/4"></div>
                    <div class="skeleton h-3 w-3/4"></div>
                </div>
            </div>
        </div>
    </div>
</template>
