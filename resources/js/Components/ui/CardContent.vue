<script setup>
import { cn } from '@/lib/utils';
const props = defineProps({ class: { type: [String, Array, Object], default: '' } });
</script>
<template><div :class="cn('p-4', props.class)"><slot /></div></template>
