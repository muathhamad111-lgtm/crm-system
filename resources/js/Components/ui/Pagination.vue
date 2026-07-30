<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import { ChevronRight, ChevronLeft } from 'lucide-vue-next';
import { cn, num } from '@/lib/utils';

/**
 * Laravel paginator rendering, RTL-correct.
 *
 * Laravel emits prev/next as "&laquo; Previous"/"Next &raquo;" — those glyphs
 * point the wrong way for Arabic, so the arrows are drawn as icons here and the
 * raw labels are only used for numeric pages. In RTL "previous" sits on the
 * right, which is where the ChevronRight naturally reads as "back".
 */
const props = defineProps({
    /** A Laravel length-aware paginator object (links, current_page, …). */
    paginator: { type: Object, required: true },
    class: { type: [String, Array, Object], default: '' },
});

const links = computed(() => props.paginator?.links ?? []);
const numbered = computed(() => links.value.slice(1, -1));
const prev = computed(() => links.value[0] ?? null);
const next = computed(() => links.value[links.value.length - 1] ?? null);
const show = computed(() => (props.paginator?.last_page ?? 1) > 1);

const from = computed(() => props.paginator?.from ?? 0);
const to = computed(() => props.paginator?.to ?? 0);
const total = computed(() => props.paginator?.total ?? 0);

const base = 'inline-flex h-8 min-w-8 items-center justify-center rounded-md border px-2 text-xs font-semibold tabular-nums transition-colors';
const idle = 'border-border bg-card text-foreground hover:bg-muted';
const active = 'border-primary bg-primary text-primary-foreground';
const off = 'pointer-events-none border-border bg-card text-muted-foreground/40';
</script>

<template>
    <div v-if="show || total" :class="cn('flex flex-wrap items-center justify-between gap-3', props.class)">
        <p class="text-xs text-muted-foreground">
            عرض <span class="font-semibold tabular-nums text-foreground">{{ num(from) }}</span>
            –<span class="font-semibold tabular-nums text-foreground">{{ num(to) }}</span>
            من <span class="font-semibold tabular-nums text-foreground">{{ num(total) }}</span>
        </p>

        <nav v-if="show" class="flex items-center gap-1" aria-label="ترقيم الصفحات">
            <Link :href="prev?.url || '#'" :class="cn(base, prev?.url ? idle : off)" aria-label="السابق">
                <ChevronRight class="size-4" />
            </Link>

            <Link v-for="(link, i) in numbered" :key="i" :href="link.url || '#'"
                :class="cn(base, link.active ? active : (link.url ? idle : off))"
                :aria-current="link.active ? 'page' : undefined" v-html="link.label" />

            <Link :href="next?.url || '#'" :class="cn(base, next?.url ? idle : off)" aria-label="التالي">
                <ChevronLeft class="size-4" />
            </Link>
        </nav>
    </div>
</template>
