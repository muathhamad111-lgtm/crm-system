<script setup>
import { computed } from 'vue';
import { Search, X } from 'lucide-vue-next';
import { cn } from '@/lib/utils';
import Button from '@/Components/ui/Button.vue';

/**
 * The search + filters strip that sits above every list.
 *
 * The search box is built in (it is on every list); everything else goes in the
 * default slot as `<Select>`s, which the responsive grid lays out for you.
 *
 *   <FilterBar v-model="q" placeholder="بحث…" :active="hasFilters" @reset="reset">
 *     <Select … />
 *   </FilterBar>
 */
const props = defineProps({
    modelValue: { type: String, default: '' },
    placeholder: { type: String, default: 'بحث…' },
    /** Show the reset button (i.e. at least one filter is applied). */
    active: { type: Boolean, default: false },
    /** Filter columns at the widest breakpoint. */
    columns: { type: Number, default: 4 },
    hideSearch: { type: Boolean, default: false },
    class: { type: [String, Array, Object], default: '' },
});
defineEmits(['update:modelValue', 'reset']);

const cols = {
    2: 'sm:grid-cols-2',
    3: 'sm:grid-cols-2 lg:grid-cols-3',
    4: 'sm:grid-cols-2 lg:grid-cols-4',
    5: 'sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5',
};
const grid = computed(() => cols[props.columns] ?? cols[4]);
</script>

<template>
    <div :class="cn('rounded-lg border border-border bg-card p-3', props.class)">
        <div :class="cn('grid gap-2', grid)">
            <div v-if="!hideSearch" class="relative">
                <Search class="pointer-events-none absolute inset-y-0 left-3 my-auto size-4 text-muted-foreground"
                    aria-hidden="true" />
                <input type="search" :value="modelValue" :placeholder="placeholder" :aria-label="placeholder"
                    @input="$emit('update:modelValue', $event.target.value)"
                    class="h-10 w-full rounded-md border border-input bg-card pe-3 ps-9 text-base text-foreground
                           placeholder:text-muted-foreground focus-visible:border-ring focus-visible:outline-none
                           focus-visible:ring-[3px] focus-visible:ring-ring/15" />
            </div>

            <slot />
        </div>

        <div v-if="active || $slots.trailing" class="mt-2 flex flex-wrap items-center justify-between gap-2">
            <div class="flex flex-wrap items-center gap-2">
                <slot name="trailing" />
            </div>
            <Button v-if="active" variant="ghost" size="sm" @click="$emit('reset')">
                <X class="size-3.5" /> مسح عوامل التصفية
            </Button>
        </div>
    </div>
</template>
