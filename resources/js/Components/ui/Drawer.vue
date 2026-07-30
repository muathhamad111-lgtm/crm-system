<script setup>
import { onBeforeUnmount, watch } from 'vue';
import { X } from 'lucide-vue-next';
import { cn } from '@/lib/utils';

/**
 * Side panel for secondary flows that would otherwise need a full page —
 * filters on mobile, record previews, quick edits.
 *
 * In RTL the panel slides in from the inline-start edge (screen left) so it does
 * not cover the sidebar, and its enter transition is mirrored to match.
 */
const props = defineProps({
    open: { type: Boolean, default: false },
    title: { type: String, default: '' },
    description: { type: String, default: '' },
    /** 'start' (screen left in RTL) | 'end' (screen right in RTL) */
    side: { type: String, default: 'start' },
    width: { type: String, default: 'max-w-md' },
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
        <Transition enter-active-class="transition-opacity duration-150" enter-from-class="opacity-0"
            leave-active-class="transition-opacity duration-100" leave-to-class="opacity-0">
            <div v-if="open" class="fixed inset-0 z-50 bg-foreground/40 backdrop-blur-[2px]" @click="close" />
        </Transition>

        <Transition
            enter-active-class="transition-transform duration-200 ease-out"
            :enter-from-class="side === 'start' ? '-translate-x-full' : 'translate-x-full'"
            leave-active-class="transition-transform duration-150 ease-in"
            :leave-to-class="side === 'start' ? '-translate-x-full' : 'translate-x-full'">
            <aside v-if="open" dir="rtl" role="dialog" aria-modal="true"
                :class="cn(
                    'fixed inset-y-0 z-50 flex w-full flex-col border-border bg-card shadow-elevated',
                    side === 'start' ? 'left-0 border-e' : 'right-0 border-s',
                    width, props.class,
                )">
                <header v-if="title || $slots.header" class="flex items-start justify-between gap-3 border-b border-border px-4 py-3">
                    <slot name="header">
                        <div class="min-w-0">
                            <h2 class="section-title truncate text-foreground">{{ title }}</h2>
                            <p v-if="description" class="mt-0.5 text-xs text-muted-foreground">{{ description }}</p>
                        </div>
                    </slot>
                    <button type="button" aria-label="إغلاق" @click="close"
                        class="-m-1 shrink-0 rounded-md p-1.5 text-muted-foreground hover:bg-muted hover:text-foreground">
                        <X class="size-4" />
                    </button>
                </header>

                <div class="flex-1 overflow-y-auto p-4">
                    <slot />
                </div>

                <footer v-if="$slots.footer" class="border-t border-border px-4 py-3">
                    <slot name="footer" />
                </footer>
            </aside>
        </Transition>
    </Teleport>
</template>
