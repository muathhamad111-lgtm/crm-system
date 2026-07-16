<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import Button from '@/Components/ui/Button.vue';
import Input from '@/Components/ui/Input.vue';
import Label from '@/Components/ui/Label.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { LogIn } from 'lucide-vue-next';

defineProps({
    canResetPassword: { type: Boolean },
    status: { type: String },
});

const form = useForm({ email: '', password: '', remember: false });

const submit = () => {
    form.post(route('login'), { onFinish: () => form.reset('password') });
};
</script>

<template>
    <GuestLayout>
        <Head title="تسجيل الدخول" />

        <div class="mb-6 text-center">
            <h1 class="text-2xl font-bold">مرحبًا بعودتك</h1>
            <p class="mt-1 text-sm text-muted-foreground">سجّل دخولك للمتابعة إلى لوحة الخدمة</p>
        </div>

        <div v-if="status" class="mb-4 rounded-lg bg-success/10 p-3 text-sm font-medium text-success">{{ status }}</div>

        <form @submit.prevent="submit" class="space-y-4">
            <div>
                <Label for="email">البريد الإلكتروني</Label>
                <Input id="email" type="email" class="mt-1.5" v-model="form.email" required autofocus autocomplete="username" placeholder="you@example.com" />
                <p v-if="form.errors.email" class="mt-1 text-xs text-destructive">{{ form.errors.email }}</p>
            </div>

            <div>
                <div class="flex items-center justify-between">
                    <Label for="password">كلمة المرور</Label>
                    <Link v-if="canResetPassword" :href="route('password.request')" class="text-xs text-primary hover:underline">نسيت كلمة المرور؟</Link>
                </div>
                <Input id="password" type="password" class="mt-1.5" v-model="form.password" required autocomplete="current-password" placeholder="••••••••" />
                <p v-if="form.errors.password" class="mt-1 text-xs text-destructive">{{ form.errors.password }}</p>
            </div>

            <label class="flex items-center gap-2 text-sm text-muted-foreground">
                <input type="checkbox" v-model="form.remember" class="size-4 rounded border-input" />
                تذكّرني
            </label>

            <Button type="submit" class="w-full gap-2" variant="accent" :disabled="form.processing">
                <LogIn class="size-4" /> تسجيل الدخول
            </Button>
        </form>

        <div class="my-5 flex items-center gap-3 text-xs text-muted-foreground">
            <div class="h-px flex-1 bg-border"></div> أو <div class="h-px flex-1 bg-border"></div>
        </div>

        <a href="/auth/google/redirect"
            class="flex h-10 w-full items-center justify-center gap-2 rounded-md border border-border bg-card text-sm font-medium transition-colors hover:bg-muted">
            <svg class="size-4" viewBox="0 0 24 24"><path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92a5.06 5.06 0 0 1-2.2 3.32v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.1Z"/><path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84A11 11 0 0 0 12 23Z"/><path fill="#FBBC05" d="M5.84 14.1a6.6 6.6 0 0 1 0-4.2V7.06H2.18a11 11 0 0 0 0 9.88l3.66-2.84Z"/><path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1A11 11 0 0 0 2.18 7.06L5.84 9.9C6.71 7.31 9.14 5.38 12 5.38Z"/></svg>
            المتابعة عبر Google
        </a>

        <p class="mt-6 text-center text-sm text-muted-foreground">
            ليس لديك حساب؟
            <Link :href="route('register')" class="font-medium text-primary hover:underline">أنشئ حسابًا</Link>
        </p>
    </GuestLayout>
</template>
