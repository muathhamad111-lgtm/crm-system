<script setup>
import { inject, computed } from 'vue';
import { cn } from '@/lib/utils';
const props = defineProps({
    value: { type: String, required: true },
    class: { type: [String, Array, Object], default: '' },
});
const tabs = inject('tabs');
const show = computed(() => tabs.active.value === props.value);
</script>
<template><div v-if="show" role="tabpanel" :class="cn('mt-4', props.class)"><slot /></div></template>
