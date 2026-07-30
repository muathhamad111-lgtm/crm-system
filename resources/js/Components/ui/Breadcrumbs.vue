<script setup>
import { Link } from '@inertiajs/vue3';
import { ChevronLeft } from 'lucide-vue-next';
import { cn } from '@/lib/utils';

/**
 * RTL breadcrumb trail. The separator chevron points right-to-left (‹) which is
 * the correct "next crumb" direction for Arabic, so it is not mirrored.
 */
const props = defineProps({
    items: { type: Array, default: () => [] },
    class: { type: [String, Array, Object], default: '' },
});
</script>

<template>
    <nav v-if="items.length" :class="cn('flex items-center gap-1 text-xs text-muted-foreground', props.class)"
        aria-label="مسار التنقل">
        <template v-for="(crumb, i) in items" :key="i">
            <ChevronLeft v-if="i > 0" class="size-3.5 shrink-0 opacity-50" aria-hidden="true" />
            <Link v-if="crumb.href && i < items.length - 1" :href="crumb.href"
                class="truncate rounded-sm transition-colors hover:text-foreground">
                {{ crumb.label }}
            </Link>
            <span v-else class="truncate" :class="i === items.length - 1 && 'font-semibold text-foreground'"
                :aria-current="i === items.length - 1 ? 'page' : undefined">
                {{ crumb.label }}
            </span>
        </template>
    </nav>
</template>
