<script setup>
import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AppShell from '@/Layouts/AppShell.vue';
import PageHero from '@/Components/PageHero.vue';
import KpiCard from '@/Components/KpiCard.vue';
import Card from '@/Components/ui/Card.vue';
import CardContent from '@/Components/ui/CardContent.vue';
import Badge from '@/Components/ui/Badge.vue';
import Button from '@/Components/ui/Button.vue';
import Tabs from '@/Components/ui/Tabs.vue';
import TabsList from '@/Components/ui/TabsList.vue';
import TabsTrigger from '@/Components/ui/TabsTrigger.vue';
import TabsContent from '@/Components/ui/TabsContent.vue';
import { APPOINTMENT_STATUS, statusLabel } from '@/lib/labels';
import { fmtFullDateTimeAr, timeAgoAr } from '@/lib/date';
import {
    CalendarDays, Plus, MapPin, Video, Phone, GraduationCap, Workflow, HelpCircle,
    Clock, CalendarCheck, ListChecks, Hourglass, Hash, ArrowLeft,
} from 'lucide-vue-next';

const props = defineProps({
    upcoming: { type: Array, default: () => [] },
    past: { type: Array, default: () => [] },
    stats: { type: Object, default: () => ({}) },
});

const MODE_META = {
    phone: { icon: Phone, label: 'مكالمة هاتفية' },
    video: { icon: Video, label: 'اجتماع مرئي' },
    onsite: { icon: MapPin, label: 'حضوري' },
    training: { icon: GraduationCap, label: 'جلسة تدريبية' },
    followup: { icon: Workflow, label: 'متابعة' },
    other: { icon: HelpCircle, label: 'أخرى' },
};
const modeMeta = (m) => MODE_META[m] ?? MODE_META.other;
</script>

<template>
    <Head title="مواعيدي" />
    <AppShell>
        <div class="glass-stage space-y-6">
            <PageHero title="مواعيدي" subtitle="تابع مواعيدك القادمة والسابقة واحجز موعداً جديداً" :icon="CalendarDays">
                <template #actions>
                    <Button :href="route('appointments.create')" variant="secondary" class="bg-white/15 text-white hover:bg-white/25">
                        <Plus class="size-4" /> حجز موعد
                    </Button>
                </template>
            </PageHero>

            <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
                <KpiCard label="إجمالي المواعيد" :value="stats.total ?? 0" :icon="ListChecks" tone="primary" />
                <KpiCard label="القادمة" :value="stats.upcoming ?? 0" :icon="CalendarCheck" tone="accent" />
                <KpiCard label="المؤكدة" :value="stats.confirmed ?? 0" :icon="Clock" tone="success" />
                <KpiCard label="بانتظار التأكيد" :value="stats.pending ?? 0" :icon="Hourglass" tone="warning" />
            </div>

            <Tabs model-value="upcoming">
                <TabsList>
                    <TabsTrigger value="upcoming" class="data-[state=active]:bg-card data-[state=active]:text-foreground data-[state=active]:shadow-sm">
                        القادمة ({{ upcoming.length }})
                    </TabsTrigger>
                    <TabsTrigger value="past" class="data-[state=active]:bg-card data-[state=active]:text-foreground data-[state=active]:shadow-sm">
                        السابقة ({{ past.length }})
                    </TabsTrigger>
                </TabsList>

                <TabsContent value="upcoming">
                    <div v-if="upcoming.length" class="grid gap-4 md:grid-cols-2">
                        <Card v-for="a in upcoming" :key="a.id" class="transition-shadow hover:shadow-elevated">
                            <CardContent class="p-5">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="flex items-center gap-3 min-w-0">
                                        <div class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-primary-soft text-primary">
                                            <component :is="modeMeta(a.type?.mode).icon" class="size-5" />
                                        </div>
                                        <div class="min-w-0">
                                            <p class="font-bold text-foreground truncate">{{ a.type?.name_ar ?? 'موعد' }}</p>
                                            <p class="mt-0.5 flex items-center gap-1 text-xs text-muted-foreground tabular-nums">
                                                <Hash class="size-3" />{{ a.appointment_number }}
                                            </p>
                                        </div>
                                    </div>
                                    <Badge :variant="statusLabel(APPOINTMENT_STATUS, a.status).tone">
                                        {{ statusLabel(APPOINTMENT_STATUS, a.status).label }}
                                    </Badge>
                                </div>

                                <div class="mt-4 space-y-1.5 text-sm">
                                    <p class="flex items-center gap-2 text-foreground">
                                        <CalendarDays class="size-4 text-muted-foreground" />
                                        {{ fmtFullDateTimeAr(a.starts_at) }}
                                    </p>
                                    <p class="flex items-center gap-2 text-muted-foreground">
                                        <Clock class="size-4" /> {{ modeMeta(a.type?.mode).label }} · {{ a.duration_minutes }} دقيقة
                                    </p>
                                    <p v-if="a.location" class="flex items-center gap-2 text-muted-foreground">
                                        <MapPin class="size-4" /> {{ a.location }}
                                    </p>
                                    <p class="text-xs text-primary">{{ timeAgoAr(a.starts_at) }}</p>
                                </div>

                                <div class="mt-4 flex justify-end">
                                    <Button :href="route('appointments.show', a.id)" variant="outline" size="sm">
                                        التفاصيل <ArrowLeft class="size-4" />
                                    </Button>
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                    <Card v-else>
                        <CardContent class="py-12 text-center">
                            <CalendarDays class="mx-auto size-10 text-muted-foreground/60" />
                            <p class="mt-3 text-sm text-muted-foreground">لا توجد مواعيد قادمة.</p>
                            <Button :href="route('appointments.create')" class="mt-4"><Plus class="size-4" /> احجز موعداً</Button>
                        </CardContent>
                    </Card>
                </TabsContent>

                <TabsContent value="past">
                    <Card v-if="past.length">
                        <CardContent class="p-0 divide-y divide-border">
                            <Link v-for="a in past" :key="a.id" :href="route('appointments.show', a.id)"
                                class="flex items-center justify-between gap-3 p-4 transition-colors hover:bg-muted/40">
                                <div class="flex items-center gap-3 min-w-0">
                                    <div class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-muted text-muted-foreground">
                                        <component :is="modeMeta(a.type?.mode).icon" class="size-4" />
                                    </div>
                                    <div class="min-w-0">
                                        <p class="font-medium text-foreground truncate">{{ a.type?.name_ar ?? 'موعد' }}</p>
                                        <p class="text-xs text-muted-foreground tabular-nums">{{ fmtFullDateTimeAr(a.starts_at) }}</p>
                                    </div>
                                </div>
                                <Badge :variant="statusLabel(APPOINTMENT_STATUS, a.status).tone">
                                    {{ statusLabel(APPOINTMENT_STATUS, a.status).label }}
                                </Badge>
                            </Link>
                        </CardContent>
                    </Card>
                    <Card v-else>
                        <CardContent class="py-12 text-center">
                            <p class="text-sm text-muted-foreground">لا توجد مواعيد سابقة.</p>
                        </CardContent>
                    </Card>
                </TabsContent>
            </Tabs>
        </div>
    </AppShell>
</template>
