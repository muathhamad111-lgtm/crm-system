<script setup>
import { computed, useId } from 'vue';
import { cn } from '@/lib/utils';

const props = defineProps({
    modelValue: { type: String, default: '' },
    label: { type: String, default: '' },
    id: { type: String, default: null },
    class: { type: [String, Array, Object], default: '' },
});
defineEmits(['update:modelValue']);

const uid = useId();
const fid = computed(() => props.id || `ta-${uid}`);

const plain = 'flex min-h-[80px] w-full rounded-md border border-input bg-card px-3 py-2 text-sm shadow-sm placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring disabled:opacity-50';
const floaty = 'peer min-h-[96px] w-full rounded-md border border-input bg-card px-3 pt-6 pb-2 text-sm shadow-sm placeholder:text-transparent focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring disabled:opacity-50';
const labelCls = 'pointer-events-none absolute right-3 text-muted-foreground transition-all duration-150 '
    + 'top-3.5 text-sm '
    + 'peer-focus:top-2 peer-focus:text-[11px] peer-focus:font-medium peer-focus:text-primary '
    + 'peer-[:not(:placeholder-shown)]:top-2 peer-[:not(:placeholder-shown)]:text-[11px]';
</script>

<template>
    <div v-if="label" class="relative">
        <textarea :id="fid" :value="modelValue" @input="$emit('update:modelValue', $event.target.value)"
            placeholder=" " :class="cn(floaty, props.class)"></textarea>
        <label :for="fid" :class="labelCls">{{ label }}</label>
    </div>
    <textarea v-else :value="modelValue" @input="$emit('update:modelValue', $event.target.value)"
        :class="cn(plain, props.class)"></textarea>
</template>
