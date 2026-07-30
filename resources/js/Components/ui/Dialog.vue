<script setup>
import { onBeforeUnmount, watch } from 'vue';
import { cn } from '@/lib/utils';
import { X } from 'lucide-vue-next';

const props = defineProps({
    open: { type: Boolean, default: false },
    title: { type: String, default: '' },
    description: { type: String, default: '' },
    /** Suppress the corner close button (e.g. a dialog that must be answered). */
    hideClose: { type: Boolean, default: false },
    class: { type: [String, Array, Object], default: '' },
});
const emit = defineEmits(['update:open', 'close']);

function close() {
    emit('update:open', false);
    emit('close');
}

function onKey(e) {
    if (e.key === 'Escape') close();
}

function lock(on) {
    if (typeof document === 'undefined') return;
    document.body.style.overflow = on ? 'hidden' : '';
    if (on) document.addEventListener('keydown', onKey);
    else document.removeEventListener('keydown', onKey);
}

watch(() => props.open, lock);
onBeforeUnmount(() => lock(false));
</script>

<template>
    <Teleport to="body">
        <Transition
            enter-active-class="transition duration-150 ease-out" enter-from-class="opacity-0"
            leave-active-class="transition duration-100 ease-in" leave-to-class="opacity-0">
            <div v-if="open" dir="rtl" class="fixed inset-0 z-50 flex items-end justify-center p-0 sm:items-center sm:p-4"
                role="dialog" aria-modal="true">
                <div class="absolute inset-0 bg-foreground/45 backdrop-blur-[2px]" @click="close"></div>

                <div :class="cn(
                    'relative z-10 max-h-[92vh] w-full overflow-y-auto border border-border bg-card p-5 shadow-elevated',
                    'rounded-t-xl sm:max-w-lg sm:rounded-xl',
                    props.class,
                )">
                    <button v-if="!hideClose" type="button" @click="close" aria-label="إغلاق"
                        class="absolute end-4 top-4 rounded-md p-1 text-muted-foreground transition-colors hover:bg-muted hover:text-foreground">
                        <X class="size-4" />
                    </button>

                    <div v-if="title || description" class="mb-4 pe-8">
                        <h2 v-if="title" class="section-title text-foreground">{{ title }}</h2>
                        <p v-if="description" class="mt-1 text-sm text-muted-foreground">{{ description }}</p>
                    </div>

                    <slot />
                </div>
            </div>
        </Transition>
    </Teleport>
</template>
