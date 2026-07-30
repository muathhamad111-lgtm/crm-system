<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import { Loader2 } from 'lucide-vue-next';
import { cn } from '@/lib/utils';

const props = defineProps({
    variant: { type: String, default: 'default' },
    size: { type: String, default: 'default' },
    as: { type: String, default: 'button' },
    href: { type: String, default: null },
    type: { type: String, default: 'button' },
    disabled: { type: Boolean, default: false },
    /** Shows a spinner and blocks interaction — for in-flight form submits. */
    loading: { type: Boolean, default: false },
    class: { type: [String, Array, Object], default: '' },
});

const variants = {
    default: 'bg-primary text-primary-foreground hover:bg-primary-deep',
    accent: 'bg-accent text-accent-foreground hover:brightness-110',
    destructive: 'bg-destructive text-destructive-foreground hover:brightness-110',
    success: 'bg-success text-success-foreground hover:brightness-110',
    warning: 'bg-warning text-warning-foreground hover:brightness-105',
    outline: 'border border-input bg-card text-foreground hover:border-primary/40 hover:bg-muted',
    secondary: 'bg-secondary text-secondary-foreground hover:bg-muted',
    ghost: 'text-foreground hover:bg-muted',
    'ghost-muted': 'text-muted-foreground hover:bg-muted hover:text-foreground',
    link: 'text-primary underline-offset-4 hover:underline',
};
const sizes = {
    default: 'h-10 px-4 text-base',
    sm: 'h-8 rounded-md px-3 text-sm',
    lg: 'h-11 px-6 text-lg',
    icon: 'h-10 w-10',
    'icon-sm': 'h-8 w-8 rounded-md',
};

const isBlocked = computed(() => props.disabled || props.loading);

const classes = computed(() => cn(
    'inline-flex select-none items-center justify-center gap-2 whitespace-nowrap rounded-md font-semibold',
    'transition-[background-color,border-color,color,box-shadow,filter] duration-150',
    'focus-visible:outline-none focus-visible:ring-[3px] focus-visible:ring-ring/30',
    'disabled:pointer-events-none disabled:opacity-50 [&_svg]:size-4 [&_svg]:shrink-0',
    variants[props.variant] ?? variants.default,
    sizes[props.size] ?? sizes.default,
    props.class,
));

const tag = computed(() => (props.href ? Link : props.as));
</script>

<template>
    <component :is="tag" :href="href" :type="href ? undefined : type" :disabled="href ? undefined : isBlocked"
        :aria-disabled="isBlocked || undefined" :aria-busy="loading || undefined" :class="classes">
        <Loader2 v-if="loading" class="animate-spin" aria-hidden="true" />
        <slot />
    </component>
</template>
