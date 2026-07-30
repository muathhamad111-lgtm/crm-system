<script setup>
import { ShieldCheck, Sparkles, Clock } from 'lucide-vue-next';

/**
 * Unauthenticated shell — a brand panel that states what the platform is, and a
 * calm, centred form column. The brand panel is hidden below `lg`, where the
 * form is the only thing that matters.
 */
const highlights = [
    { icon: ShieldCheck, text: 'مسارات عمل متخصّصة واعتماد إشرافي' },
    { icon: Clock, text: 'محرّك SLA وتنبيهات آلية للتجاوزات' },
    { icon: Sparkles, text: 'لوحة مقترحات وتقييم رضا العملاء' },
];
</script>

<template>
    <div dir="rtl" class="min-h-screen bg-background lg:grid lg:grid-cols-[1.1fr_1fr] xl:grid-cols-[1.25fr_1fr]">
        <!-- Brand panel -->
        <div class="hidden flex-col justify-between bg-brand-panel p-12 text-brand-panel-foreground lg:flex">
            <div class="flex items-center gap-3">
                <span class="flex size-11 items-center justify-center rounded-lg bg-white/12 text-xl font-bold">ط</span>
                <span class="leading-tight">
                    <span class="block font-bold">نظام إدارة طلبات العملاء</span>
                    <span class="block text-sm text-brand-panel-foreground/60">TTS — بوابة الخدمة الموحّدة</span>
                </span>
            </div>

            <div class="max-w-md space-y-7">
                <h2 class="text-3xl font-bold leading-snug">
                    منصّة موحّدة لإدارة الطلبات<br />ومتابعة الخدمة والرضا
                </h2>
                <ul class="space-y-4">
                    <li v-for="(item, i) in highlights" :key="i" class="flex items-start gap-3 text-brand-panel-foreground/85">
                        <span class="mt-0.5 flex size-8 shrink-0 items-center justify-center rounded-md bg-white/12" aria-hidden="true">
                            <component :is="item.icon" class="size-4" />
                        </span>
                        <span class="text-base">{{ item.text }}</span>
                    </li>
                </ul>
            </div>

            <p class="text-xs text-brand-panel-foreground/45">© {{ new Date().getFullYear() }} جميع الحقوق محفوظة</p>
        </div>

        <!-- Form panel -->
        <div class="flex min-h-screen flex-col items-center justify-center p-5 sm:p-8 lg:min-h-0">
            <div class="w-full max-w-md">
                <div class="mb-7 flex items-center justify-center gap-3 lg:hidden">
                    <span class="flex size-10 items-center justify-center rounded-lg bg-primary text-lg font-bold text-primary-foreground">ط</span>
                    <span class="text-lg font-bold">نظام إدارة طلبات العملاء</span>
                </div>

                <div class="rounded-xl border border-border bg-card p-6 sm:p-7">
                    <slot />
                </div>
            </div>
        </div>
    </div>
</template>
