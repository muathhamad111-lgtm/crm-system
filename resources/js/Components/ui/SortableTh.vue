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

const active = computed(() => props.sortKey === props.col);
const icon = computed(() => (!active.value ? ArrowUpDown : props.sortDir === 'asc' ? ArrowUp : ArrowDown));
const ariaSort = computed(() => (!active.value ? 'none' : props.sortDir === 'asc' ? 'ascending' : 'descending'));
</script>
<template>
    <th scope="col" :aria-sort="ariaSort"
        :class="cn('h-10 whitespace-nowrap px-3 align-middle text-xs font-bold text-muted-foreground', props.class)"
        :style="{ textAlign: align }">
        <button type="button" @click="emit('sort', col)"
            :class="cn('inline-flex items-center gap-1 rounded-sm transition-colors hover:text-foreground', active && 'text-foreground')">
            <slot />
            <component :is="icon" :class="cn('size-3', active ? 'opacity-90' : 'opacity-40')" aria-hidden="true" />
        </button>
    </th>
</template>
