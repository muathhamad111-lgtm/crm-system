<script setup>
import { computed } from 'vue';
import { cn } from '@/lib/utils';
import { toneSoft } from '@/lib/tone';

/** The tinted square that fronts a section title, list row or stat. */
const props = defineProps({
    icon: { type: [Object, Function], required: true },
    tone: { type: String, default: 'primary' },
    size: { type: String, default: 'md' },
    class: { type: [String, Array, Object], default: '' },
});

const box = { sm: 'size-7 rounded-md', md: 'size-9 rounded-lg', lg: 'size-11 rounded-xl' };
const glyph = { sm: 'size-3.5', md: 'size-4', lg: 'size-5' };

const classes = computed(() => cn(
    'inline-flex shrink-0 items-center justify-center',
    box[props.size] ?? box.md,
    toneSoft(props.tone),
    props.class,
));
</script>

<template>
    <span :class="classes" aria-hidden="true">
        <component :is="icon" :class="glyph[size] ?? glyph.md" />
    </span>
</template>
