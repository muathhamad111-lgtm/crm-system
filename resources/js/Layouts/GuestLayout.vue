<script setup>
import { ShieldCheck, Sparkles, Clock } from 'lucide-vue-next';
</script>

<template>
    <div dir="rtl" class="min-h-screen bg-background lg:grid lg:grid-cols-2">
        <!-- Brand panel -->
        <div class="relative hidden overflow-hidden lg:flex lg:flex-col lg:justify-between p-12 text-white"
            style="background-image: var(--gradient-hero);">
            <div class="absolute inset-0 opacity-20"
                style="background-image: radial-gradient(closest-side at 70% 20%, rgba(255,255,255,.5), transparent);"></div>
            <div class="relative flex items-center gap-3">
                <div class="flex size-11 items-center justify-center rounded-xl bg-white/15 backdrop-blur-sm font-bold text-lg">ط</div>
                <div class="leading-tight">
                    <p class="font-bold">نظام إدارة طلبات العملاء</p>
                    <p class="text-sm text-white/70">TTS — بوابة الخدمة الموحّدة</p>
                </div>
            </div>

            <div class="relative space-y-6">
                <h1 class="text-3xl font-bold leading-snug">منصّة موحّدة لإدارة الطلبات<br />ومتابعة الخدمة والرضا</h1>
                <ul class="space-y-3 text-white/85">
                    <li class="flex items-center gap-3"><ShieldCheck class="size-5" /> مسارات عمل متخصّصة واعتماد إشرافي</li>
                    <li class="flex items-center gap-3"><Clock class="size-5" /> محرّك SLA وتنبيهات آلية للتجاوزات</li>
                    <li class="flex items-center gap-3"><Sparkles class="size-5" /> لوحة مقترحات وتقييم رضا العملاء</li>
                </ul>
            </div>

            <p class="relative text-xs text-white/50">© {{ new Date().getFullYear() }} جميع الحقوق محفوظة</p>
        </div>

        <!-- Form panel -->
        <div class="flex min-h-screen flex-col items-center justify-center p-6 lg:min-h-0">
            <div class="w-full max-w-md">
                <div class="mb-8 flex items-center justify-center gap-3 lg:hidden">
                    <div class="flex size-10 items-center justify-center rounded-xl bg-primary text-primary-foreground font-bold">ط</div>
                    <p class="font-bold text-lg">نظام إدارة طلبات العملاء</p>
                </div>
                <div class="rounded-2xl border border-border bg-card p-7 shadow-elevated">
                    <slot />
                </div>
            </div>
        </div>
    </div>
</template>
