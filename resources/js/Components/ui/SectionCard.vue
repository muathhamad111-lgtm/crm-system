<script setup>
import { cn } from '@/lib/utils';
import IconChip from '@/Components/ui/IconChip.vue';

/**
 * A titled content block — the workhorse container of the platform.
 *
 * Replaces the hand-rolled "card + bordered header strip + icon + h2" pattern
 * that every page used to re-implement slightly differently.
 *
 *   <SectionCard title="…" :icon="Inbox" tone="primary" flush>
 *     <template #actions><Button …/></template>
 *     …body…
 *     <template #footer>…</template>
 *   </SectionCard>
 *
 * `flush` removes body padding — use it when the body is a table or a
 * divide-y list that should meet the card edges.
 */
const props = defineProps({
    title: { type: String, default: '' },
    description: { type: String, default: '' },
    icon: { type: [Object, Function], default: null },
    tone: { type: String, default: 'primary' },
    /** Remove padding from the body region. */
    flush: { type: Boolean, default: false },
    /** Emphasise the card border with a status tone. */
    borderTone: { type: String, default: '' },
    class: { type: [String, Array, Object], default: '' },
    bodyClass: { type: [String, Array, Object], default: '' },
});

const borderTones = {
    warning: 'border-warning/40',
    destructive: 'border-destructive/40',
    success: 'border-success/40',
    info: 'border-info/40',
    primary: 'border-primary/40',
    accent: 'border-accent/40',
};
</script>

<template>
    <section data-slot="card" :class="cn(
        'flex flex-col overflow-hidden rounded-lg border border-border bg-card text-card-foreground',
        borderTone && borderTones[borderTone],
        props.class,
    )">
        <header v-if="title || $slots.header || $slots.actions"
            class="flex items-center justify-between gap-3 border-b border-border px-4 py-3">
            <slot name="header">
                <div class="flex min-w-0 items-center gap-2.5">
                    <IconChip v-if="icon" :icon="icon" :tone="tone" size="sm" />
                    <div class="min-w-0">
                        <h2 class="section-title truncate text-foreground">{{ title }}</h2>
                        <p v-if="description" class="mt-0.5 truncate text-xs text-muted-foreground">{{ description }}</p>
                    </div>
                    <slot name="badge" />
                </div>
            </slot>
            <div v-if="$slots.actions" class="flex shrink-0 items-center gap-2">
                <slot name="actions" />
            </div>
        </header>

        <div :class="cn('flex-1', flush ? '' : 'p-4', props.bodyClass)">
            <slot />
        </div>

        <footer v-if="$slots.footer" class="border-t border-border px-4 py-3">
            <slot name="footer" />
        </footer>
    </section>
</template>
