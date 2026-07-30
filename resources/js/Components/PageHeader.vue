<script setup>
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import Breadcrumbs from '@/Components/ui/Breadcrumbs.vue';
import { cn } from '@/lib/utils';
import { trailFor } from '@/lib/breadcrumbs';

/**
 * The single page-heading component for the whole platform.
 *
 * Renders the breadcrumb trail, the page title and its supporting copy, and a
 * primary-action area — flat, on the page canvas, so the content below is what
 * carries visual weight.
 *
 * Breadcrumbs are derived from the sidebar navigation automatically; pass
 * `trail` to append detail-page crumbs (e.g. a request number), or `breadcrumbs`
 * to take over the trail completely.
 */
const props = defineProps({
    title: { type: String, required: true },
    subtitle: { type: String, default: '' },
    /** Extra crumbs appended after the auto-derived trail. */
    trail: { type: Array, default: () => [] },
    /** Full override of the trail. */
    breadcrumbs: { type: Array, default: null },
    /** Hide the trail entirely (e.g. on the dashboard). */
    hideBreadcrumbs: { type: Boolean, default: false },
    class: { type: [String, Array, Object], default: '' },
});

const page = usePage();

const crumbs = computed(() => {
    if (props.hideBreadcrumbs) return [];
    if (props.breadcrumbs) return props.breadcrumbs;

    const auth = page.props.auth ?? {};
    const isStaff = !!auth.isStaff;
    const base = trailFor(page.url, { isStaff, isAdmin: !!auth.isAdmin, isCustomer: !isStaff });
    return [...base, ...props.trail];
});
</script>

<template>
    <header :class="cn('mb-5', props.class)">
        <Breadcrumbs v-if="crumbs.length > 1" :items="crumbs" class="mb-2" />

        <div class="flex flex-wrap items-start justify-between gap-x-4 gap-y-3">
            <div class="min-w-0 flex-1">
                <div class="flex flex-wrap items-center gap-2.5">
                    <h1 class="page-title text-foreground">{{ title }}</h1>
                    <slot name="badge" />
                </div>
                <p v-if="subtitle" class="mt-1 max-w-2xl text-base text-muted-foreground">{{ subtitle }}</p>
            </div>

            <div v-if="$slots.actions" class="flex flex-wrap items-center gap-2">
                <slot name="actions" />
            </div>
        </div>

        <div v-if="$slots.default" class="mt-4">
            <slot />
        </div>
    </header>
</template>
