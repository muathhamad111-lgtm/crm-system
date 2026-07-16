<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import Button from '@/Components/ui/Button.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({ status: { type: String } });
const form = useForm({});
const submit = () => form.post(route('verification.send'));
const verificationLinkSent = computed(() => props.status === 'verification-link-sent');
</script>

<template>
    <GuestLayout>
        <Head title="توثيق البريد" />
        <div class="mb-4 text-center">
            <h1 class="text-2xl font-bold">توثيق بريدك الإلكتروني</h1>
        </div>
        <p class="text-sm text-muted-foreground">شكرًا لتسجيلك! يرجى توثيق بريدك عبر الرابط الذي أرسلناه إليك. إن لم يصلك، سنرسل رابطًا جديدًا.</p>
        <div v-if="verificationLinkSent" class="mt-4 rounded-lg bg-success/10 p-3 text-sm font-medium text-success">تم إرسال رابط توثيق جديد إلى بريدك.</div>
        <form @submit.prevent="submit" class="mt-6 flex items-center justify-between">
            <Button type="submit" variant="accent" :disabled="form.processing">إعادة إرسال الرابط</Button>
            <Link :href="route('logout')" method="post" as="button" class="text-sm text-muted-foreground hover:underline">تسجيل الخروج</Link>
        </form>
    </GuestLayout>
</template>
