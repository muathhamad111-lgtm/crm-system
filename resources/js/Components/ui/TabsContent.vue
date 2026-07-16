<script setup>
import { inject, computed } from 'vue';
const props = defineProps({ value: { type: String, required: true } });
const tabs = inject('tabs');
const show = computed(() => tabs.active.value === props.value);
</script>
<template><div v-if="show" role="tabpanel" class="mt-4"><slot /></div></template>
