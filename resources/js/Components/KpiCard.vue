<script setup>
/**
 * Backwards-compatible alias for the platform stat card.
 *
 * Kept so existing `<KpiCard>` call sites keep working; new code should use
 * `@/Components/ui/StatCard.vue` directly.
 */
import StatCard from '@/Components/ui/StatCard.vue';

defineProps({
    label: { type: String, required: true },
    value: { type: [String, Number], default: 0 },
    hint: { type: String, default: '' },
    icon: { type: [Object, Function], default: null },
    tone: { type: String, default: 'primary' },
    href: { type: String, default: null },
    formatNumber: { type: Boolean, default: true },
    class: { type: [String, Array, Object], default: '' },
});
</script>

<template>
    <StatCard v-bind="$props" />
</template>
