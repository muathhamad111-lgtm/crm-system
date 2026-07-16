<script setup>
import { cn } from '@/lib/utils';
const props = defineProps({ class: { type: [String, Array, Object], default: '' } });
</script>
<template>
    <div class="relative w-full overflow-x-auto">
        <table :class="cn('w-full caption-bottom text-sm border-collapse', props.class)"><slot /></table>
    </div>
</template>
