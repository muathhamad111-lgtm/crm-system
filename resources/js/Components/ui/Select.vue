<script setup>
import { computed, ref, useId } from 'vue';
import { cn } from '@/lib/utils';

const props = defineProps({
    modelValue: { type: [String, Number, null], default: '' },
    label: { type: String, default: '' },
    id: { type: String, default: null },
    class: { type: [String, Array, Object], default: '' },
});
defineEmits(['update:modelValue']);

const uid = useId();
const fid = computed(() => props.id || `sel-${uid}`);
const focused = ref(false);
// Selects can't use :placeholder-shown, so float when focused or a value is chosen.
const floated = computed(() => focused.value || (props.modelValue !== '' && props.modelValue !== null && props.modelValue !== undefined));

const plain = 'flex h-10 w-full rounded-md border border-input bg-card px-3 py-2 text-sm shadow-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring disabled:opacity-50';
const floaty = 'h-14 w-full rounded-md border border-input bg-card px-3 pt-6 pb-1.5 text-sm shadow-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring disabled:opacity-50';
</script>

<template>
    <div v-if="label" class="relative">
        <select :id="fid" :value="modelValue" @change="$emit('update:modelValue', $event.target.value)"
            @focus="focused = true" @blur="focused = false" :class="cn(floaty, props.class)">
            <slot />
        </select>
        <label :for="fid" :class="cn(
            'pointer-events-none absolute right-3 text-muted-foreground transition-all duration-150',
            floated ? 'top-2 text-[11px] font-medium' : 'top-1/2 -translate-y-1/2 text-sm',
            focused && 'text-primary',
        )">{{ label }}</label>
    </div>
    <select v-else :value="modelValue" @change="$emit('update:modelValue', $event.target.value)"
        :class="cn(plain, props.class)">
        <slot />
    </select>
</template>
