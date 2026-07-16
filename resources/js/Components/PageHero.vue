<script setup>
const props = defineProps({
    title: { type: String, required: true },
    subtitle: { type: String, default: '' },
    icon: { type: [Object, Function], default: null },
});
</script>

<template>
    <div class="relative overflow-hidden rounded-2xl p-6 sm:p-8 text-white shadow-elevated"
        style="background-image: var(--gradient-hero);">
        <div class="absolute inset-0 opacity-20"
            style="background-image: radial-gradient(closest-side, rgba(255,255,255,.4), transparent);"></div>
        <div class="relative flex items-center gap-4">
            <div v-if="icon" class="flex size-12 items-center justify-center rounded-xl bg-white/15 backdrop-blur-sm">
                <component :is="icon" class="size-6" />
            </div>
            <div class="min-w-0">
                <h1 class="text-2xl sm:text-3xl font-bold tracking-tight">{{ title }}</h1>
                <p v-if="subtitle" class="mt-1 text-white/80 text-sm sm:text-base">{{ subtitle }}</p>
            </div>
            <div class="mr-auto flex items-center gap-2">
                <slot name="actions" />
            </div>
        </div>
        <div v-if="$slots.default" class="relative mt-5">
            <slot />
        </div>
    </div>
</template>
