<script setup>
import { computed } from 'vue';
import { cn } from '@/lib/utils';
const props = defineProps({
    variant: { type: String, default: 'default' },
    class: { type: [String, Array, Object], default: '' },
});
const variants = {
    default: 'border-transparent bg-primary text-primary-foreground',
    secondary: 'border-transparent bg-secondary text-secondary-foreground',
    accent: 'border-transparent bg-accent text-accent-foreground',
    outline: 'text-foreground border-border',
    success: 'border-transparent bg-success text-success-foreground',
    warning: 'border-transparent bg-warning text-warning-foreground',
    destructive: 'border-transparent bg-destructive text-destructive-foreground',
    muted: 'border-transparent bg-muted text-muted-foreground',
    info: 'border-transparent bg-info text-info-foreground',
};
const classes = computed(() => cn(
    'inline-flex items-center gap-1 rounded-full border px-2.5 py-0.5 text-xs font-bold tabular-nums',
    variants[props.variant] ?? variants.default, props.class,
));
</script>
<template><span :class="classes"><slot /></span></template>
