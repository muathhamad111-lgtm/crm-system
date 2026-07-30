<script setup>
import { cn } from '@/lib/utils';
const props = defineProps({ class: { type: [String, Array, Object], default: '' } });
</script>
<template>
    <div class="table-scroll relative w-full">
        <table :class="cn('w-full caption-bottom border-collapse text-base', props.class)"><slot /></table>
    </div>
</template>
