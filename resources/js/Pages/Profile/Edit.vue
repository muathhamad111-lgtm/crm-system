<script setup>
import { computed } from 'vue';
import { Head, usePage } from '@inertiajs/vue3';
import AppShell from '@/Layouts/AppShell.vue';
import PageHero from '@/Components/PageHero.vue';
import Card from '@/Components/ui/Card.vue';
import CardHeader from '@/Components/ui/CardHeader.vue';
import CardTitle from '@/Components/ui/CardTitle.vue';
import CardContent from '@/Components/ui/CardContent.vue';
import CardDescription from '@/Components/ui/CardDescription.vue';
import Badge from '@/Components/ui/Badge.vue';
import Avatar from '@/Components/ui/Avatar.vue';
import DeleteUserForm from './Partials/DeleteUserForm.vue';
import UpdatePasswordForm from './Partials/UpdatePasswordForm.vue';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm.vue';
import { UserCircle } from 'lucide-vue-next';
import { ROLE_LABELS } from '@/lib/labels';

defineProps({
    mustVerifyEmail: { type: Boolean },
    status: { type: String },
});

const page = usePage();
const user = computed(() => page.props.auth.user);
const roles = computed(() => page.props.auth.roles ?? []);
</script>

<template>
    <Head title="حسابي" />
    <AppShell>
        <div class="mx-auto max-w-3xl space-y-6">
            <PageHero title="حسابي" subtitle="إدارة بياناتك الشخصية وكلمة المرور" :icon="UserCircle" />

            <Card>
                <CardContent class="flex items-center gap-4 pt-6">
                    <Avatar :name="user?.full_name ?? user?.name" class="size-14 text-lg" />
                    <div class="min-w-0">
                        <p class="text-lg font-bold">{{ user?.full_name ?? user?.name }}</p>
                        <p class="text-sm text-muted-foreground" dir="ltr">{{ user?.email }}</p>
                        <div class="mt-1.5 flex flex-wrap gap-1">
                            <Badge v-for="r in roles" :key="r" variant="secondary">{{ ROLE_LABELS[r] ?? r }}</Badge>
                            <Badge v-if="!roles.length" variant="muted">عميل</Badge>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>المعلومات الشخصية</CardTitle>
                    <CardDescription>حدّث اسمك وبريدك الإلكتروني.</CardDescription>
                </CardHeader>
                <CardContent>
                    <UpdateProfileInformationForm :must-verify-email="mustVerifyEmail" :status="status" />
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>تغيير كلمة المرور</CardTitle>
                    <CardDescription>استخدم كلمة مرور طويلة وعشوائية للحفاظ على أمان حسابك.</CardDescription>
                </CardHeader>
                <CardContent>
                    <UpdatePasswordForm />
                </CardContent>
            </Card>

            <Card class="border-destructive/30">
                <CardHeader>
                    <CardTitle class="text-destructive">حذف الحساب</CardTitle>
                    <CardDescription>عند حذف حسابك تُحذف جميع بياناته نهائيًا. هذا الإجراء لا يمكن التراجع عنه.</CardDescription>
                </CardHeader>
                <CardContent>
                    <DeleteUserForm />
                </CardContent>
            </Card>
        </div>
    </AppShell>
</template>
