<script setup>
import { cn } from '@/lib/utils';

/**
 * Underlined tab rail. Scrolls horizontally rather than wrapping, so a record
 * with a dozen tabs keeps a single predictable row on every screen size.
 */
const props = defineProps({ class: { type: [String, Array, Object], default: '' } });
</script>
<template>
    <div role="tablist" class="table-scroll -mb-px border-b border-border">
        <div :class="cn('flex w-max min-w-full items-center gap-1', props.class)">
            <slot />
        </div>
    </div>
</template>
