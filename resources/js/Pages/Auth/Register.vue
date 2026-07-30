<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import Button from '@/Components/ui/Button.vue';
import Input from '@/Components/ui/Input.vue';
import Label from '@/Components/ui/Label.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { UserPlus } from 'lucide-vue-next';

import FieldError from '@/Components/ui/FieldError.vue';
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
                <Input label="الاسم الكامل" v-model="form.name" required autofocus autocomplete="name" />
                <FieldError :message="form.errors.name" />
            </div>
            <div>
                <Input label="البريد الإلكتروني" type="email" v-model="form.email" required autocomplete="username" />
                <FieldError :message="form.errors.email" />
            </div>
            <div>
                <Input label="رقم الجوال" type="tel" v-model="form.phone" autocomplete="tel" />
                <FieldError :message="form.errors.phone" />
            </div>
            <div>
                <Input label="كلمة المرور" type="password" v-model="form.password" required autocomplete="new-password" />
                <FieldError :message="form.errors.password" />
            </div>
            <div>
                <Input label="تأكيد كلمة المرور" type="password" v-model="form.password_confirmation" required autocomplete="new-password" />
            </div>
            <Button type="submit" class="w-full gap-2" variant="accent" :loading="form.processing"><UserPlus class="size-4" /> إنشاء الحساب</Button>
        </form>
        <p class="mt-6 text-center text-sm text-muted-foreground">
            لديك حساب بالفعل؟
            <Link :href="route('login')" class="font-medium text-primary hover:underline">تسجيل الدخول</Link>
        </p>
    </GuestLayout>
</template>
