<script setup>
import { cn } from '@/lib/utils';
const props = defineProps({ class: { type: [String, Array, Object], default: '' } });
</script>
<template><th :class="cn('h-11 px-3 text-start align-middle font-bold text-muted-foreground text-xs', props.class)"><slot /></th></template>
