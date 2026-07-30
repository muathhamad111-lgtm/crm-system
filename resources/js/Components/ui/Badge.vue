<script setup>
import { computed } from 'vue';
import { cn } from '@/lib/utils';

/**
 * Status chip.
 *
 * Tinted rather than solid-filled: a list of twenty rows reads far better when
 * status is a quiet colour wash than when every row carries a saturated block.
 * Pass `solid` for the rare case that needs maximum emphasis.
 */
const props = defineProps({
    variant: { type: String, default: 'default' },
    solid: { type: Boolean, default: false },
    /** Prefix the label with a filled dot in the same tone. */
    dot: { type: Boolean, default: false },
    class: { type: [String, Array, Object], default: '' },
});

const soft = {
    default: 'border-primary/20 bg-primary-soft text-primary',
    primary: 'border-primary/20 bg-primary-soft text-primary',
    secondary: 'border-border bg-secondary text-secondary-foreground',
    accent: 'border-accent/25 bg-accent-soft text-accent',
    outline: 'border-border bg-transparent text-muted-foreground',
    success: 'border-success/25 bg-success/10 text-success',
    warning: 'border-warning/35 bg-warning/15 text-warning',
    destructive: 'border-destructive/25 bg-destructive/10 text-destructive',
    info: 'border-info/25 bg-info/10 text-info',
    muted: 'border-border bg-muted text-muted-foreground',
};

const solidStyles = {
    default: 'border-transparent bg-primary text-primary-foreground',
    primary: 'border-transparent bg-primary text-primary-foreground',
    secondary: 'border-transparent bg-secondary text-secondary-foreground',
    accent: 'border-transparent bg-accent text-accent-foreground',
    outline: 'border-border text-foreground',
    success: 'border-transparent bg-success text-success-foreground',
    warning: 'border-transparent bg-warning text-warning-foreground',
    destructive: 'border-transparent bg-destructive text-destructive-foreground',
    info: 'border-transparent bg-info text-info-foreground',
    muted: 'border-transparent bg-muted text-muted-foreground',
};

const classes = computed(() => {
    const map = props.solid ? solidStyles : soft;
    return cn(
        'inline-flex max-w-full items-center gap-1.5 whitespace-nowrap rounded-full border px-2 py-0.5',
        'text-xs font-semibold tabular-nums leading-tight',
        map[props.variant] ?? map.default,
        props.class,
    );
});
</script>

<template>
    <span :class="classes">
        <span v-if="dot" class="size-1.5 shrink-0 rounded-full bg-current" aria-hidden="true"></span>
        <span class="truncate"><slot /></span>
    </span>
</template>
