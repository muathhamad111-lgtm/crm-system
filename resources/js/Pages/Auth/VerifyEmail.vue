<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import Button from '@/Components/ui/Button.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

import Alert from '@/Components/ui/Alert.vue';
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
        <Alert v-if="verificationLinkSent" variant="success" class="mt-4">تم إرسال رابط توثيق جديد إلى بريدك.</Alert>
        <form @submit.prevent="submit" class="mt-6 flex items-center justify-between">
            <Button type="submit" variant="accent" :loading="form.processing">إعادة إرسال الرابط</Button>
            <Link :href="route('logout')" method="post" as="button" class="text-sm text-muted-foreground hover:underline">تسجيل الخروج</Link>
        </form>
    </GuestLayout>
</template>
