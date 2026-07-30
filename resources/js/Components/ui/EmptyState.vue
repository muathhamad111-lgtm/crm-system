<script setup>
import { Inbox } from 'lucide-vue-next';
import { cn } from '@/lib/utils';
import { toneSoft } from '@/lib/tone';

/** The one empty-state treatment: icon, what happened, and the way forward. */
const props = defineProps({
    title: { type: String, required: true },
    description: { type: String, default: '' },
    icon: { type: [Object, Function], default: () => Inbox },
    tone: { type: String, default: 'muted' },
    size: { type: String, default: 'md' },
    class: { type: [String, Array, Object], default: '' },
});

const pad = { sm: 'px-4 py-8', md: 'px-6 py-12', lg: 'px-6 py-16' };
</script>

<template>
    <div :class="cn('flex flex-col items-center justify-center text-center', pad[size] ?? pad.md, props.class)">
        <span :class="cn('mb-3 flex size-12 items-center justify-center rounded-xl', toneSoft(tone))" aria-hidden="true">
            <component :is="icon" class="size-5" />
        </span>
        <p class="text-base font-semibold text-foreground">{{ title }}</p>
        <p v-if="description" class="mt-1 max-w-sm text-sm text-muted-foreground">{{ description }}</p>
        <div v-if="$slots.default" class="mt-4 flex flex-wrap items-center justify-center gap-2">
            <slot />
        </div>
    </div>
</template>
