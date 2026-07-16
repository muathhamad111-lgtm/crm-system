<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import Button from '@/Components/ui/Button.vue';
import Input from '@/Components/ui/Input.vue';
import Label from '@/Components/ui/Label.vue';
import { Head, useForm } from '@inertiajs/vue3';

const form = useForm({ password: '' });
const submit = () => form.post(route('password.confirm'), { onFinish: () => form.reset() });
</script>

<template>
    <GuestLayout>
        <Head title="تأكيد كلمة المرور" />
        <div class="mb-6 text-center">
            <h1 class="text-2xl font-bold">تأكيد الهوية</h1>
            <p class="mt-1 text-sm text-muted-foreground">هذه منطقة آمنة، يرجى تأكيد كلمة المرور للمتابعة</p>
        </div>
        <form @submit.prevent="submit" class="space-y-4">
            <div>
                <Label for="password">كلمة المرور</Label>
                <Input id="password" type="password" class="mt-1.5" v-model="form.password" required autofocus autocomplete="current-password" />
                <p v-if="form.errors.password" class="mt-1 text-xs text-destructive">{{ form.errors.password }}</p>
            </div>
            <Button type="submit" class="w-full" variant="accent" :disabled="form.processing">تأكيد</Button>
        </form>
    </GuestLayout>
</template>
