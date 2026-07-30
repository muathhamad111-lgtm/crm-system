<script setup>
import { cn } from '@/lib/utils';
const props = defineProps({ class: { type: [String, Array, Object], default: '' } });
</script>
<template>
    <th scope="col" :class="cn('h-10 whitespace-nowrap px-3 text-start align-middle text-xs font-bold text-muted-foreground', props.class)">
        <slot />
    </th>
</template>
