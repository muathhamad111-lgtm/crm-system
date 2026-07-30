<script setup>
import { cn } from '@/lib/utils';
const props = defineProps({ class: { type: [String, Array, Object], default: '' } });
</script>
<template>
    <div :class="cn('flex flex-col gap-1 border-b border-border px-4 py-3', props.class)"><slot /></div>
</template>
