<script setup>
import { cn } from '@/lib/utils';
const props = defineProps({ class: { type: [String, Array, Object], default: '' } });
</script>
<template>
    <thead :class="cn('bg-muted/60 [&_tr]:border-b [&_tr]:border-border', props.class)"><slot /></thead>
</template>
