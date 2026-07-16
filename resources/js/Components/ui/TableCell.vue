<script setup>
import { cn } from '@/lib/utils';
const props = defineProps({ class: { type: [String, Array, Object], default: '' } });
</script>
<template><td :class="cn('px-3 py-2.5 align-middle', props.class)"><slot /></td></template>
