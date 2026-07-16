<script setup>
import { cn } from '@/lib/utils';
const props = defineProps({ class: { type: [String, Array, Object], default: '' } });
</script>
<template>
    <div role="tablist" :class="cn('inline-flex flex-wrap items-center justify-center gap-1 rounded-lg bg-muted p-1 text-muted-foreground', props.class)">
        <slot />
    </div>
</template>
