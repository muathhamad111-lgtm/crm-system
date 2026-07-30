<script setup>
import { computed } from 'vue';
import { AlertTriangle, Info, Trash2 } from 'lucide-vue-next';
import Dialog from '@/Components/ui/Dialog.vue';
import Button from '@/Components/ui/Button.vue';
import { toneSoft } from '@/lib/tone';

/**
 * Confirmation before a consequential action.
 *
 * The default slot holds anything extra the decision needs — a reason field, a
 * list of affected records — so pages stop rebuilding this dialog by hand.
 */
const props = defineProps({
    open: { type: Boolean, default: false },
    title: { type: String, required: true },
    description: { type: String, default: '' },
    confirmLabel: { type: String, default: 'تأكيد' },
    cancelLabel: { type: String, default: 'إلغاء' },
    /** 'destructive' | 'warning' | 'primary' */
    variant: { type: String, default: 'destructive' },
    processing: { type: Boolean, default: false },
    /** Disable the confirm button (e.g. a required reason is empty). */
    disabled: { type: Boolean, default: false },
});
const emit = defineEmits(['update:open', 'confirm', 'cancel']);

const icons = { destructive: Trash2, warning: AlertTriangle, primary: Info };
const icon = computed(() => icons[props.variant] ?? icons.destructive);
const buttonVariant = computed(() => (props.variant === 'primary' ? 'default' : props.variant));

function cancel() {
    emit('update:open', false);
    emit('cancel');
}
</script>

<template>
    <Dialog :open="open" class="max-w-md" @update:open="$emit('update:open', $event)" @close="$emit('cancel')">
        <div class="flex items-start gap-3">
            <span :class="['flex size-10 shrink-0 items-center justify-center rounded-lg', toneSoft(variant)]" aria-hidden="true">
                <component :is="icon" class="size-5" />
            </span>
            <div class="min-w-0 flex-1 pe-6">
                <h2 class="section-title text-foreground">{{ title }}</h2>
                <p v-if="description" class="mt-1 text-sm text-muted-foreground">{{ description }}</p>
            </div>
        </div>

        <div v-if="$slots.default" class="mt-4">
            <slot />
        </div>

        <div class="mt-5 flex flex-wrap justify-end gap-2">
            <Button variant="outline" :disabled="processing" @click="cancel">{{ cancelLabel }}</Button>
            <Button :variant="buttonVariant" :disabled="processing || disabled" @click="$emit('confirm')">
                {{ confirmLabel }}
            </Button>
        </div>
    </Dialog>
</template>
