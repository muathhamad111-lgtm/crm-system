<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import Button from '@/Components/ui/Button.vue';
import Input from '@/Components/ui/Input.vue';
import Label from '@/Components/ui/Label.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

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
        <div v-if="status" class="mb-4 rounded-lg bg-success/10 p-3 text-sm font-medium text-success">{{ status }}</div>
        <form @submit.prevent="submit" class="space-y-4">
            <div>
                <Input label="البريد الإلكتروني" type="email" v-model="form.email" required autofocus />
                <p v-if="form.errors.email" class="mt-1 text-xs text-destructive">{{ form.errors.email }}</p>
            </div>
            <Button type="submit" class="w-full" variant="accent" :disabled="form.processing">إرسال رابط الاستعادة</Button>
        </form>
        <p class="mt-6 text-center text-sm text-muted-foreground">
            <Link :href="route('login')" class="font-medium text-primary hover:underline">العودة لتسجيل الدخول</Link>
        </p>
    </GuestLayout>
</template>
