<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import Button from '@/Components/ui/Button.vue';
import Input from '@/Components/ui/Input.vue';
import Label from '@/Components/ui/Label.vue';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps({ email: { type: String }, token: { type: String } });
const form = useForm({ token: props.token, email: props.email, password: '', password_confirmation: '' });
const submit = () => form.post(route('password.store'), { onFinish: () => form.reset('password', 'password_confirmation') });
</script>

<template>
    <GuestLayout>
        <Head title="إعادة تعيين كلمة المرور" />
        <div class="mb-6 text-center">
            <h1 class="text-2xl font-bold">تعيين كلمة مرور جديدة</h1>
        </div>
        <form @submit.prevent="submit" class="space-y-4">
            <div>
                <Input label="البريد الإلكتروني" type="email" v-model="form.email" required autocomplete="username" />
                <p v-if="form.errors.email" class="mt-1 text-xs text-destructive">{{ form.errors.email }}</p>
            </div>
            <div>
                <Input label="كلمة المرور الجديدة" type="password" v-model="form.password" required autofocus autocomplete="new-password" />
                <p v-if="form.errors.password" class="mt-1 text-xs text-destructive">{{ form.errors.password }}</p>
            </div>
            <div>
                <Input label="تأكيد كلمة المرور" type="password" v-model="form.password_confirmation" required autocomplete="new-password" />
            </div>
            <Button type="submit" class="w-full" variant="accent" :disabled="form.processing">حفظ كلمة المرور</Button>
        </form>
    </GuestLayout>
</template>
