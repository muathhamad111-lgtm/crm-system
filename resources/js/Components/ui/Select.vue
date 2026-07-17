<script setup>
import { computed, ref, useId } from 'vue';
import { cn } from '@/lib/utils';

const props = defineProps({
    modelValue: { type: [String, Number, null], default: '' },
    label: { type: String, default: '' },
    invalid: { type: Boolean, default: false },
    id: { type: String, default: null },
    class: { type: [String, Array, Object], default: '' },
});
defineEmits(['update:modelValue']);

const uid = useId();
const fid = computed(() => props.id || `sel-${uid}`);
const focused = ref(false);
const filled = computed(() => props.modelValue !== '' && props.modelValue !== null && props.modelValue !== undefined);
const isFloat = computed(() => focused.value || filled.value);

const plain = 'flex h-10 w-full rounded-md border border-input bg-card px-3 py-2 text-sm shadow-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring disabled:opacity-50';
</script>

<template>
    <div v-if="label" class="ff" :class="[isFloat && 'is-float', focused && 'is-focus', invalid && 'ff-invalid']">
        <select :id="fid" :value="modelValue"
            @change="$emit('update:modelValue', $event.target.value)"
            @focus="focused = true" @blur="focused = false"
            :class="cn('ff-control ff-select', props.class)">
            <slot />
        </select>
        <label :for="fid" class="ff-label">{{ label }}</label>
        <fieldset aria-hidden="true" class="ff-outline"><legend class="ff-legend"><span>{{ label }}</span></legend></fieldset>
    </div>
    <select v-else :value="modelValue" @change="$emit('update:modelValue', $event.target.value)"
        :class="cn(plain, props.class)">
        <slot />
    </select>
</template>
