<script setup>
import { inject, computed } from 'vue';
import { cn } from '@/lib/utils';

const props = defineProps({
    value: { type: String, required: true },
    class: { type: [String, Array, Object], default: '' },
});
const tabs = inject('tabs');
const isActive = computed(() => tabs.active.value === props.value);
</script>
<template>
    <button type="button" role="tab" :aria-selected="isActive" :data-state="isActive ? 'active' : 'inactive'"
        @click="tabs.set(value)"
        :class="cn(
            'relative inline-flex shrink-0 items-center justify-center gap-1.5 whitespace-nowrap border-b-2 px-3 py-2.5',
            'text-base font-semibold transition-colors focus-visible:outline-none focus-visible:ring-[3px] focus-visible:ring-ring/25',
            isActive
                ? 'border-primary text-primary'
                : 'border-transparent text-muted-foreground hover:border-border hover:text-foreground',
            props.class,
        )">
        <slot />
    </button>
</template>
