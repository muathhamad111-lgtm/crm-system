<script setup>
import { cn } from '@/lib/utils';
const props = defineProps({
    modelValue: { type: [String, Number, null], default: '' },
    class: { type: [String, Array, Object], default: '' },
});
defineEmits(['update:modelValue']);
</script>
<template>
    <select :value="modelValue" @change="$emit('update:modelValue', $event.target.value)"
        :class="cn('flex h-10 w-full rounded-md border border-input bg-card px-3 py-2 text-sm shadow-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring disabled:opacity-50', props.class)">
        <slot />
    </select>
</template>
