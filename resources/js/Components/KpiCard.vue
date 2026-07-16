<script setup>
import { computed } from 'vue';
import Card from '@/Components/ui/Card.vue';
import { cn, num } from '@/lib/utils';

const props = defineProps({
    label: { type: String, required: true },
    value: { type: [String, Number], default: 0 },
    hint: { type: String, default: '' },
    icon: { type: [Object, Function], default: null },
    tone: { type: String, default: 'primary' },
    formatNumber: { type: Boolean, default: true },
});

const tones = {
    primary: 'bg-primary-soft text-primary',
    accent: 'bg-accent/10 text-accent',
    success: 'bg-success/10 text-success',
    warning: 'bg-warning/15 text-warning',
    destructive: 'bg-destructive/10 text-destructive',
    muted: 'bg-muted text-muted-foreground',
};
const display = computed(() =>
    props.formatNumber && typeof props.value === 'number' ? num(props.value) : props.value);
</script>

<template>
    <Card class="p-5">
        <div class="flex items-start justify-between gap-3">
            <div class="min-w-0">
                <p class="text-sm text-muted-foreground truncate">{{ label }}</p>
                <p class="mt-1.5 text-2xl font-bold tabular-nums text-foreground">{{ display }}</p>
                <p v-if="hint" class="mt-1 text-xs text-muted-foreground">{{ hint }}</p>
            </div>
            <div v-if="icon" :class="cn('flex size-10 shrink-0 items-center justify-center rounded-lg', tones[tone] ?? tones.primary)">
                <component :is="icon" class="size-5" />
            </div>
        </div>
    </Card>
</template>
