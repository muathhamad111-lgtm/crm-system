<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import Button from '@/Components/ui/Button.vue';
import Input from '@/Components/ui/Input.vue';
import Label from '@/Components/ui/Label.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { UserPlus } from 'lucide-vue-next';

const form = useForm({ name: '', email: '', phone: '', password: '', password_confirmation: '' });
const submit = () => form.post(route('register'), { onFinish: () => form.reset('password', 'password_confirmation') });
</script>

<template>
    <GuestLayout>
        <Head title="إنشاء حساب" />
        <div class="mb-6 text-center">
            <h1 class="text-2xl font-bold">إنشاء حساب جديد</h1>
            <p class="mt-1 text-sm text-muted-foreground">سجّل بياناتك للبدء بمتابعة طلباتك</p>
        </div>
        <form @submit.prevent="submit" class="space-y-4">
            <div>
                <Label for="name">الاسم الكامل</Label>
                <Input id="name" class="mt-1.5" v-model="form.name" required autofocus autocomplete="name" />
                <p v-if="form.errors.name" class="mt-1 text-xs text-destructive">{{ form.errors.name }}</p>
            </div>
            <div>
                <Label for="email">البريد الإلكتروني</Label>
                <Input id="email" type="email" class="mt-1.5" v-model="form.email" required autocomplete="username" />
                <p v-if="form.errors.email" class="mt-1 text-xs text-destructive">{{ form.errors.email }}</p>
            </div>
            <div>
                <Label for="phone">رقم الجوال</Label>
                <Input id="phone" type="tel" class="mt-1.5" v-model="form.phone" autocomplete="tel" placeholder="05xxxxxxxx" />
                <p v-if="form.errors.phone" class="mt-1 text-xs text-destructive">{{ form.errors.phone }}</p>
            </div>
            <div>
                <Label for="password">كلمة المرور</Label>
                <Input id="password" type="password" class="mt-1.5" v-model="form.password" required autocomplete="new-password" />
                <p v-if="form.errors.password" class="mt-1 text-xs text-destructive">{{ form.errors.password }}</p>
            </div>
            <div>
                <Label for="password_confirmation">تأكيد كلمة المرور</Label>
                <Input id="password_confirmation" type="password" class="mt-1.5" v-model="form.password_confirmation" required autocomplete="new-password" />
            </div>
            <Button type="submit" class="w-full gap-2" variant="accent" :disabled="form.processing"><UserPlus class="size-4" /> إنشاء الحساب</Button>
        </form>
        <p class="mt-6 text-center text-sm text-muted-foreground">
            لديك حساب بالفعل؟
            <Link :href="route('login')" class="font-medium text-primary hover:underline">تسجيل الدخول</Link>
        </p>
    </GuestLayout>
</template>
