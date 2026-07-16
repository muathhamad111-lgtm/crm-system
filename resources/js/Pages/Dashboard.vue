<script setup>
import { Head } from '@inertiajs/vue3';
import AppShell from '@/Layouts/AppShell.vue';
import PageHero from '@/Components/PageHero.vue';
import KpiCard from '@/Components/KpiCard.vue';
import Card from '@/Components/ui/Card.vue';
import CardHeader from '@/Components/ui/CardHeader.vue';
import CardTitle from '@/Components/ui/CardTitle.vue';
import CardContent from '@/Components/ui/CardContent.vue';
import { Inbox, Clock, CheckCircle2, Star, LayoutDashboard } from 'lucide-vue-next';

const props = defineProps({
    stats: { type: Object, default: () => ({}) },
});
</script>

<template>
    <Head title="الرئيسية" />
    <AppShell>
        <div class="glass-stage space-y-6">
            <PageHero
                title="لوحة المتابعة"
                subtitle="نظرة سريعة على الطلبات والأداء"
                :icon="LayoutDashboard" />

            <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
                <KpiCard label="طلبات نشطة" :value="stats.active ?? 0" :icon="Inbox" tone="primary" />
                <KpiCard label="بانتظار المعالجة" :value="stats.pending ?? 0" :icon="Clock" tone="warning" />
                <KpiCard label="مكتملة" :value="stats.completed ?? 0" :icon="CheckCircle2" tone="success" />
                <KpiCard label="متوسط الرضا" :value="stats.csat ?? '—'" :format-number="false" :icon="Star" tone="accent" />
            </div>

            <Card>
                <CardHeader>
                    <CardTitle>آخر التحديثات</CardTitle>
                </CardHeader>
                <CardContent>
                    <p class="text-sm text-muted-foreground">سيتم عرض النشاط الأخير هنا.</p>
                </CardContent>
            </Card>
        </div>
    </AppShell>
</template>
