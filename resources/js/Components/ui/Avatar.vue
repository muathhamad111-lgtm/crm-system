<script setup>
import { cn, initials } from '@/lib/utils';
const props = defineProps({
    name: { type: String, default: '' },
    class: { type: [String, Array, Object], default: '' },
});
</script>
<template>
    <span :title="name || undefined"
        :class="cn('inline-flex size-9 shrink-0 select-none items-center justify-center rounded-full bg-primary-soft text-sm font-bold text-primary', props.class)">
        {{ initials(name) }}
    </span>
</template>
