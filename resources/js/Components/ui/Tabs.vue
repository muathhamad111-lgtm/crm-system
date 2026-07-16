<script setup>
import { provide, ref, toRef, watch } from 'vue';
const props = defineProps({ modelValue: { type: String, default: '' } });
const emit = defineEmits(['update:modelValue']);
const active = ref(props.modelValue);
watch(() => props.modelValue, (v) => { active.value = v; });
function set(v) { active.value = v; emit('update:modelValue', v); }
provide('tabs', { active, set });
</script>
<template><div><slot /></div></template>
