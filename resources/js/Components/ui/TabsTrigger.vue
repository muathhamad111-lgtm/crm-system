<script setup>
import { inject, computed } from 'vue';
import { cn } from '@/lib/utils';
const props = defineProps({ value: { type: String, required: true }, class: { type: [String, Array, Object], default: '' } });
const tabs = inject('tabs');
const isActive = computed(() => tabs.active.value === props.value);
</script>
<template>
    <button type="button" role="tab" :aria-selected="isActive" :data-state="isActive ? 'active' : 'inactive'"
        @click="tabs.set(value)"
        :class="cn('inline-flex items-center justify-center gap-1.5 whitespace-nowrap rounded-md px-3 py-1.5 text-sm font-medium transition-all', isActive ? '' : 'hover:bg-card/60', props.class)">
        <slot />
    </button>
</template>
