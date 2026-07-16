<script setup>
import { computed } from 'vue';
import { SERVICE_STATUS, statusLabel } from '@/lib/labels';

const props = defineProps({ status: { type: String, default: 'on_track' } });

const cfg = {
    on_track: { dot: 'bg-success', cls: 'border-success/30 text-success bg-success/10' },
    due_soon: { dot: 'bg-warning', cls: 'border-warning/30 text-warning bg-warning/10' },
    awaiting_customer: { dot: 'bg-warning', cls: 'border-warning/40 text-warning bg-warning/15' },
    overdue: { dot: 'bg-destructive', cls: 'border-destructive/40 text-destructive bg-destructive/10' },
    done: { dot: 'bg-success', cls: 'border-success/30 text-success bg-success/10' },
};
const c = computed(() => cfg[props.status] ?? cfg.on_track);
const label = computed(() => statusLabel(SERVICE_STATUS, props.status).label);
</script>

<template>
    <span :class="['inline-flex items-center gap-1.5 rounded-full border px-2 py-0.5 text-[11px] font-semibold', c.cls]">
        <span :class="['size-1.5 rounded-full', c.dot]"></span>
        {{ label }}
    </span>
</template>
