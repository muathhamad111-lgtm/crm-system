<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import Button from '@/Components/ui/Button.vue';
import Input from '@/Components/ui/Input.vue';
import Label from '@/Components/ui/Label.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

import FieldError from '@/Components/ui/FieldError.vue';
import Alert from '@/Components/ui/Alert.vue';
defineProps({ status: { type: String } });
const form = useForm({ email: '' });
const submit = () => form.post(route('password.email'));
</script>

<template>
    <GuestLayout>
        <Head title="نسيت كلمة المرور" />
        <div class="mb-6 text-center">
            <h1 class="text-2xl font-bold">استعادة كلمة المرور</h1>
            <p class="mt-1 text-sm text-muted-foreground">أدخل بريدك وسنرسل لك رابط إعادة التعيين</p>
        </div>
        <Alert v-if="status" variant="success" class="mb-4">{{ status }}</Alert>
        <form @submit.prevent="submit" class="space-y-4">
            <div>
                <Input label="البريد الإلكتروني" type="email" v-model="form.email" required autofocus />
                <FieldError :message="form.errors.email" />
            </div>
            <Button type="submit" class="w-full" variant="accent" :loading="form.processing">إرسال رابط الاستعادة</Button>
        </form>
        <p class="mt-6 text-center text-sm text-muted-foreground">
            <Link :href="route('login')" class="font-medium text-primary hover:underline">العودة لتسجيل الدخول</Link>
        </p>
    </GuestLayout>
</template>
