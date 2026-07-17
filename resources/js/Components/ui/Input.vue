<script setup>
import { computed, ref, useId } from 'vue';
import { cn } from '@/lib/utils';

const props = defineProps({
    modelValue: { type: [String, Number], default: '' },
    type: { type: String, default: 'text' },
    label: { type: String, default: '' },
    invalid: { type: Boolean, default: false },
    id: { type: String, default: null },
    class: { type: [String, Array, Object], default: '' },
});
defineEmits(['update:modelValue']);

const uid = useId();
const fid = computed(() => props.id || `in-${uid}`);
const focused = ref(false);
const filled = computed(() => props.modelValue !== '' && props.modelValue !== null && props.modelValue !== undefined);
const isFloat = computed(() => focused.value || filled.value);

const plain = 'flex h-10 w-full rounded-md border border-input bg-card px-3 py-2 text-sm shadow-sm transition-colors placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring disabled:cursor-not-allowed disabled:opacity-50';
</script>

<template>
    <div v-if="label" class="ff" :class="[isFloat && 'is-float', focused && 'is-focus', invalid && 'ff-invalid']">
        <input :id="fid" :type="type" :value="modelValue"
            @input="$emit('update:modelValue', $event.target.value)"
            @focus="focused = true" @blur="focused = false"
            :class="cn('ff-control', props.class)" />
        <label :for="fid" class="ff-label">{{ label }}</label>
        <fieldset aria-hidden="true" class="ff-outline"><legend class="ff-legend"><span>{{ label }}</span></legend></fieldset>
    </div>
    <input v-else :type="type" :value="modelValue" @input="$emit('update:modelValue', $event.target.value)"
        :class="cn(plain, props.class)" />
</template>
