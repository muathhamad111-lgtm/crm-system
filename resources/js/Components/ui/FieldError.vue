<script setup>
import { AlertCircle } from 'lucide-vue-next';
import { cn } from '@/lib/utils';

/** Validation message for a single field. Renders nothing when there is none. */
const props = defineProps({
    message: { type: String, default: '' },
    class: { type: [String, Array, Object], default: '' },
});
</script>

<template>
    <p v-if="message" role="alert" :class="cn('mt-1.5 flex items-center gap-1 text-xs font-medium text-destructive', props.class)">
        <AlertCircle class="size-3 shrink-0" aria-hidden="true" />
        {{ message }}
    </p>
</template>
