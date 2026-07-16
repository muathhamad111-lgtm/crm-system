<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import { cn } from '@/lib/utils';

const props = defineProps({
    variant: { type: String, default: 'default' },
    size: { type: String, default: 'default' },
    as: { type: String, default: 'button' },
    href: { type: String, default: null },
    type: { type: String, default: 'button' },
    disabled: { type: Boolean, default: false },
    class: { type: [String, Array, Object], default: '' },
});

const variants = {
    default: 'bg-primary text-primary-foreground shadow-sm hover:bg-primary-deep',
    accent: 'bg-accent text-accent-foreground shadow-sm hover:opacity-90',
    destructive: 'bg-destructive text-destructive-foreground shadow-sm hover:opacity-90',
    outline: 'border border-border bg-card hover:bg-muted text-foreground',
    secondary: 'bg-secondary text-secondary-foreground hover:bg-muted',
    ghost: 'hover:bg-muted text-foreground',
    link: 'text-primary underline-offset-4 hover:underline',
    success: 'bg-success text-success-foreground hover:opacity-90',
    warning: 'bg-warning text-warning-foreground hover:opacity-90',
};
const sizes = {
    default: 'h-10 px-4 py-2 text-sm',
    sm: 'h-8 rounded-md px-3 text-xs',
    lg: 'h-12 rounded-lg px-6 text-base',
    icon: 'h-10 w-10',
    'icon-sm': 'h-8 w-8',
};

const classes = computed(() => cn(
    'inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-md font-medium transition-colors',
    'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-1',
    'disabled:pointer-events-none disabled:opacity-50 [&_svg]:size-4 [&_svg]:shrink-0',
    variants[props.variant] ?? variants.default,
    sizes[props.size] ?? sizes.default,
    props.class,
));

const tag = computed(() => (props.href ? Link : props.as));
</script>

<template>
    <component :is="tag" :href="href" :type="href ? undefined : type" :disabled="disabled" :class="classes">
        <slot />
    </component>
</template>
