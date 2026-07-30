<script setup>
import { cn } from '@/lib/utils';
const props = defineProps({ class: { type: [String, Array, Object], default: '' } });
</script>
<template><h3 :class="cn('section-title text-foreground', props.class)"><slot /></h3></template>
