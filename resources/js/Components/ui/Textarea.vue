<script setup>
import { cn } from '@/lib/utils';
const props = defineProps({
    modelValue: { type: String, default: '' },
    class: { type: [String, Array, Object], default: '' },
});
defineEmits(['update:modelValue']);
</script>
<template>
    <textarea :value="modelValue" @input="$emit('update:modelValue', $event.target.value)"
        :class="cn('flex min-h-[80px] w-full rounded-md border border-input bg-card px-3 py-2 text-sm shadow-sm placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring disabled:opacity-50', props.class)"></textarea>
</template>
