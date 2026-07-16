<script setup>
import { cn } from '@/lib/utils';
const props = defineProps({ orientation: { type: String, default: 'horizontal' }, class: { type: [String, Array, Object], default: '' } });
</script>
<template>
    <div :class="cn('shrink-0 bg-border', props.orientation === 'vertical' ? 'h-full w-px' : 'h-px w-full', props.class)"></div>
</template>
