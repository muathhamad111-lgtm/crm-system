<script setup>
import { ref, computed } from 'vue';
import { useForm } from '@inertiajs/vue3';
import Input from '@/Components/ui/Input.vue';
import Textarea from '@/Components/ui/Textarea.vue';
import Button from '@/Components/ui/Button.vue';
import Badge from '@/Components/ui/Badge.vue';
import { Save, RotateCcw } from 'lucide-vue-next';

const props = defineProps({
    settingKey: { type: String, required: true },
    value: { default: null },
});

const isObject = computed(() => props.value !== null && typeof props.value === 'object');

function toEditable(v) {
    if (v === null || v === undefined) return '';
    if (typeof v === 'object') return JSON.stringify(v, null, 2);
    return String(v);
}

const original = toEditable(props.value);
const form = useForm({ key: props.settingKey, value: original });
const dirty = computed(() => form.value !== original);

function save() {
    form.post('/admin/settings', { preserveScroll: true, preserveState: true });
}
function reset() {
    form.value = original;
}
</script>

<template>
    <div class="rounded-lg border border-border bg-card p-3">
        <div class="mb-2 flex items-center justify-between gap-2">
            <code class="rounded bg-muted px-2 py-0.5 font-mono text-xs text-foreground" dir="ltr">{{ settingKey }}</code>
            <Badge v-if="isObject" variant="muted" class="text-[10px]">JSON</Badge>
        </div>
        <Textarea v-if="isObject" v-model="form.value" dir="ltr" rows="4" class="font-mono text-xs" />
        <Input v-else v-model="form.value" dir="ltr" class="text-sm" />
        <div class="mt-2 flex items-center justify-end gap-2">
            <Button v-if="dirty" size="sm" variant="ghost" @click="reset">
                <RotateCcw class="size-3.5" /> تراجع
            </Button>
            <Button size="sm" variant="accent" :disabled="!dirty || form.processing" @click="save">
                <Save class="size-3.5" /> حفظ
            </Button>
        </div>
    </div>
</template>
