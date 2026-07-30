<script setup>
import { computed } from 'vue';
import { AlertTriangle, CheckCircle2, Info, XCircle, X } from 'lucide-vue-next';
import { cn } from '@/lib/utils';

/** Inline status message — form results, flash notices, workflow warnings. */
const props = defineProps({
    variant: { type: String, default: 'info' },
    title: { type: String, default: '' },
    dismissible: { type: Boolean, default: false },
    class: { type: [String, Array, Object], default: '' },
});
defineEmits(['dismiss']);

const styles = {
    info: { box: 'border-info/30 bg-info/8 text-foreground', mark: 'text-info', icon: Info },
    success: { box: 'border-success/30 bg-success/8 text-foreground', mark: 'text-success', icon: CheckCircle2 },
    warning: { box: 'border-warning/40 bg-warning/10 text-foreground', mark: 'text-warning', icon: AlertTriangle },
    destructive: { box: 'border-destructive/35 bg-destructive/8 text-foreground', mark: 'text-destructive', icon: XCircle },
};
const style = computed(() => styles[props.variant] ?? styles.info);
</script>

<template>
    <div role="status" :class="cn('flex items-start gap-3 rounded-lg border p-3', style.box, props.class)">
        <component :is="style.icon" :class="cn('mt-0.5 size-4 shrink-0', style.mark)" aria-hidden="true" />
        <div class="min-w-0 flex-1">
            <p v-if="title" class="text-base font-semibold">{{ title }}</p>
            <div :class="cn('text-sm', title ? 'mt-0.5 text-muted-foreground' : '')">
                <slot />
            </div>
        </div>
        <button v-if="dismissible" type="button" aria-label="إغلاق" @click="$emit('dismiss')"
            class="-m-1 shrink-0 rounded-md p-1 text-muted-foreground hover:bg-muted hover:text-foreground">
            <X class="size-3.5" />
        </button>
    </div>
</template>
