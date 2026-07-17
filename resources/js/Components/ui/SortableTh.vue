<script setup>
import { computed } from 'vue';
import { cn } from '@/lib/utils';
import { ArrowUpDown, ArrowUp, ArrowDown } from 'lucide-vue-next';
const props = defineProps({
    col: { type: String, required: true },
    sortKey: { type: [String, null], default: null },
    sortDir: { type: String, default: 'asc' },
    class: { type: [String, Array, Object], default: '' },
    align: { type: String, default: 'start' },
});
const emit = defineEmits(['sort']);
const icon = computed(() => (props.sortKey !== props.col ? ArrowUpDown : props.sortDir === 'asc' ? ArrowUp : ArrowDown));
const active = computed(() => props.sortKey === props.col);
</script>
<template>
    <th :class="cn('h-11 px-3 align-middle font-bold text-muted-foreground text-xs', props.class)" :style="{ textAlign: align }">
        <button type="button" class="inline-flex items-center gap-1 hover:text-foreground transition-colors" :class="active && 'text-foreground'" @click="emit('sort', col)">
            <slot /><component :is="icon" class="size-3 opacity-70" />
        </button>
    </th>
</template>
