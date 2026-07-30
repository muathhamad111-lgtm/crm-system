<script setup>
import { cn } from '@/lib/utils';
const props = defineProps({
    for: { type: String, default: null },
    required: { type: Boolean, default: false },
    class: { type: [String, Array, Object], default: '' },
});
</script>
<template>
    <label :for="props.for" :class="cn('inline-flex items-center gap-1 text-sm font-semibold leading-none text-foreground', props.class)">
        <slot />
        <span v-if="required" class="text-destructive" aria-hidden="true">*</span>
    </label>
</template>
