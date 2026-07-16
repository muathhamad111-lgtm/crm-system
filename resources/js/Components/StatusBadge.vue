<script setup>
import Badge from '@/Components/ui/Badge.vue';
import { REQUEST_STATUS, statusLabel } from '@/lib/labels';
const props = defineProps({ status: { type: String, default: '' } });
</script>
<template>
    <Badge :variant="statusLabel(REQUEST_STATUS, status).tone">{{ statusLabel(REQUEST_STATUS, status).label }}</Badge>
</template>
