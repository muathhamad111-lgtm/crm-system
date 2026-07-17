<script setup>
import { computed, useId } from 'vue';
import { cn } from '@/lib/utils';

const props = defineProps({
    modelValue: { type: [String, Number], default: '' },
    type: { type: String, default: 'text' },
    label: { type: String, default: '' },
    id: { type: String, default: null },
    class: { type: [String, Array, Object], default: '' },
});
defineEmits(['update:modelValue']);

const uid = useId();
const fid = computed(() => props.id || `in-${uid}`);

const plain = 'flex h-10 w-full rounded-md border border-input bg-card px-3 py-2 text-sm shadow-sm transition-colors placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring disabled:cursor-not-allowed disabled:opacity-50';
// Floating variant: taller field; the label sits inside then floats up on focus / when filled.
const floaty = 'peer h-14 w-full rounded-md border border-input bg-card px-3 pt-6 pb-1.5 text-sm shadow-sm transition-colors placeholder:text-transparent focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring disabled:cursor-not-allowed disabled:opacity-50';
const labelCls = 'pointer-events-none absolute right-3 text-muted-foreground transition-all duration-150 '
    + 'top-1/2 -translate-y-1/2 text-sm '
    + 'peer-focus:top-2 peer-focus:translate-y-0 peer-focus:text-[11px] peer-focus:font-medium peer-focus:text-primary '
    + 'peer-[:not(:placeholder-shown)]:top-2 peer-[:not(:placeholder-shown)]:translate-y-0 peer-[:not(:placeholder-shown)]:text-[11px]';
</script>

<template>
    <div v-if="label" class="relative">
        <input :id="fid" :type="type" :value="modelValue" @input="$emit('update:modelValue', $event.target.value)"
            placeholder=" " :class="cn(floaty, props.class)" />
        <label :for="fid" :class="labelCls">{{ label }}</label>
    </div>
    <input v-else :type="type" :value="modelValue" @input="$emit('update:modelValue', $event.target.value)"
        :class="cn(plain, props.class)" />
</template>
