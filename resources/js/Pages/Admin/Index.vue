<script setup>
import { ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AppShell from '@/Layouts/AppShell.vue';
import PageHero from '@/Components/PageHero.vue';
import Card from '@/Components/ui/Card.vue';
import CardContent from '@/Components/ui/CardContent.vue';
import Button from '@/Components/ui/Button.vue';
import Badge from '@/Components/ui/Badge.vue';
import Tabs from '@/Components/ui/Tabs.vue';
import TabsList from '@/Components/ui/TabsList.vue';
import TabsTrigger from '@/Components/ui/TabsTrigger.vue';
import TabsContent from '@/Components/ui/TabsContent.vue';
import Table from '@/Components/ui/Table.vue';
import TableHeader from '@/Components/ui/TableHeader.vue';
import TableBody from '@/Components/ui/TableBody.vue';
import TableRow from '@/Components/ui/TableRow.vue';
import TableHead from '@/Components/ui/TableHead.vue';
import TableCell from '@/Components/ui/TableCell.vue';
import { Settings } from 'lucide-vue-next';
import { ROLE_LABELS } from '@/lib/labels';

const props = defineProps({
    categories: { type: Array, default: () => [] },
    products: { type: Array, default: () => [] },
    teams: { type: Array, default: () => [] },
    settings: { type: Object, default: () => ({}) },
    multipliers: { type: Array, default: () => [] },
    staff: { type: Array, default: () => [] },
    capabilityCounts: { type: Object, default: () => ({}) },
    rolePermissions: { type: Object, default: () => ({}) },
    capabilityMeta: { type: Array, default: () => [] },
    notificationTemplates: { type: Array, default: () => [] },
    integrationSettings: { type: Array, default: () => [] },
    systemIntegrations: { type: Array, default: () => [] },
    auditLog: { type: Array, default: () => [] },
    options: { type: Object, default: () => ({}) },
});

const tab = ref('categories');
</script>

<template>
    <Head title="إدارة النظام" />
    <AppShell>
        <div class="space-y-6">
            <PageHero title="إدارة النظام" subtitle="إعدادات المنصّة والصلاحيات والتكاملات" :icon="Settings" />

            <Tabs v-model="tab">
                <TabsList>
                    <TabsTrigger value="categories">التصنيفات</TabsTrigger>
                    <TabsTrigger value="products">المنتجات والخدمات</TabsTrigger>
                    <TabsTrigger value="teams">الفرق</TabsTrigger>
                    <TabsTrigger value="staff">فريق العمل</TabsTrigger>
                    <TabsTrigger value="permissions">الصلاحيات</TabsTrigger>
                    <TabsTrigger value="sla">SLA والأولويات</TabsTrigger>
                    <TabsTrigger value="templates">القوالب</TabsTrigger>
                    <TabsTrigger value="integrations">التكاملات</TabsTrigger>
                    <TabsTrigger value="audit">سجل التدقيق</TabsTrigger>
                </TabsList>

                <TabsContent value="categories">
                    <Card class="p-4"><Table>
                        <TableHeader><TableRow><TableHead>التصنيف</TableHead><TableHead>الفريق</TableHead><TableHead>SLA (ساعة)</TableHead><TableHead>الحالة</TableHead></TableRow></TableHeader>
                        <TableBody>
                            <TableRow v-for="c in categories" :key="c.id">
                                <TableCell class="font-medium">{{ c.name_ar }}</TableCell>
                                <TableCell class="text-sm text-muted-foreground">{{ c.target_team }}</TableCell>
                                <TableCell class="tabular-nums">{{ c.sla_hours }}</TableCell>
                                <TableCell><Badge :variant="c.active ? 'success' : 'muted'">{{ c.active ? 'مفعّل' : 'معطّل' }}</Badge></TableCell>
                            </TableRow>
                        </TableBody>
                    </Table></Card>
                </TabsContent>

                <TabsContent value="products">
                    <Card class="p-4"><Table>
                        <TableHeader><TableRow><TableHead>المنتج</TableHead><TableHead>النوع</TableHead><TableHead>الحالة</TableHead></TableRow></TableHeader>
                        <TableBody>
                            <TableRow v-for="p in products" :key="p.id">
                                <TableCell class="font-medium">{{ p.name_ar }}</TableCell>
                                <TableCell class="text-sm text-muted-foreground">{{ p.type }}</TableCell>
                                <TableCell><Badge :variant="p.active ? 'success' : 'muted'">{{ p.active ? 'مفعّل' : 'معطّل' }}</Badge></TableCell>
                            </TableRow>
                        </TableBody>
                    </Table></Card>
                </TabsContent>

                <TabsContent value="teams">
                    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                        <Card v-for="t in teams" :key="t.id" class="p-4 flex items-center gap-3">
                            <span class="size-4 rounded-full" :style="{ background: t.color }"></span>
                            <span class="font-medium">{{ t.name_ar }}</span>
                        </Card>
                    </div>
                </TabsContent>

                <TabsContent value="staff">
                    <Card class="p-4"><Table>
                        <TableHeader><TableRow><TableHead>الاسم</TableHead><TableHead>البريد</TableHead><TableHead>الأدوار</TableHead></TableRow></TableHeader>
                        <TableBody>
                            <TableRow v-for="s in staff" :key="s.id">
                                <TableCell class="font-medium">{{ s.full_name }}</TableCell>
                                <TableCell class="text-sm text-muted-foreground" dir="ltr">{{ s.email }}</TableCell>
                                <TableCell class="flex flex-wrap gap-1">
                                    <Badge v-for="r in (s.roles ?? [])" :key="r" variant="secondary">{{ ROLE_LABELS[r] ?? r }}</Badge>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table></Card>
                </TabsContent>

                <TabsContent value="permissions">
                    <Card class="p-4"><Table>
                        <TableHeader><TableRow><TableHead>الدور</TableHead><TableHead>عدد الصلاحيات المفعّلة</TableHead></TableRow></TableHeader>
                        <TableBody>
                            <TableRow v-for="(count, role) in capabilityCounts" :key="role">
                                <TableCell class="font-medium">{{ ROLE_LABELS[role] ?? role }}</TableCell>
                                <TableCell class="tabular-nums">{{ count }}</TableCell>
                            </TableRow>
                        </TableBody>
                    </Table></Card>
                </TabsContent>

                <TabsContent value="sla">
                    <Card class="p-4"><Table>
                        <TableHeader><TableRow><TableHead>الأولوية</TableHead><TableHead>المضاعف</TableHead></TableRow></TableHeader>
                        <TableBody>
                            <TableRow v-for="m in multipliers" :key="m.priority">
                                <TableCell class="font-medium">{{ m.label_ar ?? m.priority }}</TableCell>
                                <TableCell class="tabular-nums">×{{ m.multiplier }}</TableCell>
                            </TableRow>
                        </TableBody>
                    </Table></Card>
                </TabsContent>

                <TabsContent value="templates">
                    <Card class="p-4"><Table>
                        <TableHeader><TableRow><TableHead>الحدث</TableHead><TableHead>الاسم</TableHead><TableHead>القنوات</TableHead></TableRow></TableHeader>
                        <TableBody>
                            <TableRow v-for="t in notificationTemplates" :key="t.id ?? t.event_key">
                                <TableCell class="text-sm text-muted-foreground">{{ t.event_key }}</TableCell>
                                <TableCell class="font-medium">{{ t.name_ar }}</TableCell>
                                <TableCell class="text-xs">{{ Array.isArray(t.channels) ? t.channels.join('، ') : t.channels }}</TableCell>
                            </TableRow>
                        </TableBody>
                    </Table></Card>
                </TabsContent>

                <TabsContent value="integrations">
                    <div class="grid gap-3 sm:grid-cols-2">
                        <Card v-for="i in systemIntegrations" :key="i.id ?? i.key" class="p-4 flex items-center justify-between">
                            <div><p class="font-medium">{{ i.name_ar ?? i.key }}</p><p class="text-xs text-muted-foreground">{{ i.key }}</p></div>
                            <Badge :variant="i.active ? 'success' : 'muted'">{{ i.active ? 'مفعّل' : 'معطّل' }}</Badge>
                        </Card>
                    </div>
                </TabsContent>

                <TabsContent value="audit">
                    <Card class="p-4"><Table>
                        <TableHeader><TableRow><TableHead>الإجراء</TableHead><TableHead>الكيان</TableHead><TableHead>المنفّذ</TableHead></TableRow></TableHeader>
                        <TableBody>
                            <TableRow v-for="a in auditLog" :key="a.id">
                                <TableCell class="text-sm">{{ a.action }}</TableCell>
                                <TableCell class="text-sm text-muted-foreground">{{ a.entity_type }}</TableCell>
                                <TableCell class="text-sm text-muted-foreground">{{ a.actor_email ?? '—' }}</TableCell>
                            </TableRow>
                            <TableRow v-if="!auditLog.length"><TableCell colspan="3" class="py-8 text-center text-muted-foreground">لا يوجد سجل.</TableCell></TableRow>
                        </TableBody>
                    </Table></Card>
                </TabsContent>
            </Tabs>
        </div>
    </AppShell>
</template>
