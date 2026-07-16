<script setup>
import Badge from '@/Components/ui/Badge.vue';
import { REQUEST_PRIORITY, statusLabel } from '@/lib/labels';
const props = defineProps({ priority: { type: String, default: '' } });
</script>
<template>
    <Badge :variant="statusLabel(REQUEST_PRIORITY, priority).tone" class="gap-1">
        <span class="size-1.5 rounded-full bg-current opacity-70"></span>
        {{ statusLabel(REQUEST_PRIORITY, priority).label }}
    </Badge>
</template>
