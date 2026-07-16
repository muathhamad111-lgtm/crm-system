<script setup>
import { cn, initials } from '@/lib/utils';
const props = defineProps({ name: { type: String, default: '' }, class: { type: [String, Array, Object], default: '' } });
</script>
<template>
    <div :class="cn('inline-flex items-center justify-center rounded-full bg-primary-soft text-primary font-bold text-sm size-9 select-none', props.class)">
        {{ initials(name) }}
    </div>
</template>
