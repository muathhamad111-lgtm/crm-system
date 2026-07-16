<script setup>
import { cn } from '@/lib/utils';
const props = defineProps({ class: { type: [String, Array, Object], default: '' } });
</script>
<template>
    <div data-slot="card" :class="cn('rounded-xl border border-border bg-card text-card-foreground shadow-card', props.class)">
        <slot />
    </div>
</template>
