<script setup>
import { watch } from 'vue';
import { cn } from '@/lib/utils';
import { X } from 'lucide-vue-next';

const props = defineProps({
    open: { type: Boolean, default: false },
    title: { type: String, default: '' },
    description: { type: String, default: '' },
    class: { type: [String, Array, Object], default: '' },
});
const emit = defineEmits(['update:open', 'close']);

function close() {
    emit('update:open', false);
    emit('close');
}

watch(() => props.open, (v) => {
    if (typeof document !== 'undefined') {
        document.body.style.overflow = v ? 'hidden' : '';
    }
});
</script>

<template>
    <Teleport to="body">
        <Transition
            enter-active-class="transition duration-150 ease-out"
            enter-from-class="opacity-0" enter-to-class="opacity-100"
            leave-active-class="transition duration-100 ease-in"
            leave-from-class="opacity-100" leave-to-class="opacity-0">
            <div v-if="open" dir="rtl" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="close"></div>
                <div :class="cn('relative z-10 w-full max-w-lg rounded-xl border border-border bg-card p-6 shadow-elevated max-h-[85vh] overflow-y-auto', props.class)">
                    <button type="button" @click="close"
                        class="absolute top-4 left-4 rounded-md p-1 text-muted-foreground hover:bg-muted transition-colors">
                        <X class="size-4" />
                    </button>
                    <div v-if="title || description" class="mb-4 pl-8">
                        <h2 v-if="title" class="text-lg font-bold text-foreground">{{ title }}</h2>
                        <p v-if="description" class="text-sm text-muted-foreground mt-1">{{ description }}</p>
                    </div>
                    <slot />
                </div>
            </div>
        </Transition>
    </Teleport>
</template>
