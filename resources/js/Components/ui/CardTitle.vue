<script setup>
import { cn } from '@/lib/utils';
const props = defineProps({ class: { type: [String, Array, Object], default: '' } });
</script>
<template>
    <h3 :class="cn('font-bold leading-none tracking-tight text-lg', props.class)"><slot /></h3>
</template>
