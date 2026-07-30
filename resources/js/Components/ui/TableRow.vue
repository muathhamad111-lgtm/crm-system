<script setup>
import { cn } from '@/lib/utils';
const props = defineProps({ class: { type: [String, Array, Object], default: '' } });
</script>
<template>
    <tr :class="cn('border-b border-border transition-colors hover:bg-muted/50 data-[state=selected]:bg-primary-soft/60', props.class)">
        <slot />
    </tr>
</template>
