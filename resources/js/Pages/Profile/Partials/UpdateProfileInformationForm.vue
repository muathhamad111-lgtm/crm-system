<script setup>
import Alert from '@/Components/ui/Alert.vue';
import Button from '@/Components/ui/Button.vue';
import Input from '@/Components/ui/Input.vue';
import Label from '@/Components/ui/Label.vue';
import { Link, useForm, usePage } from '@inertiajs/vue3';

import FieldError from '@/Components/ui/FieldError.vue';
defineProps({ mustVerifyEmail: { type: Boolean }, status: { type: String } });
const user = usePage().props.auth.user;
const form = useForm({ name: user.name, email: user.email });
</script>

<template>
    <form @submit.prevent="form.patch(route('profile.update'))" class="space-y-4">
        <div>
            <Input label="الاسم" v-model="form.name" required autocomplete="name" />
            <FieldError :message="form.errors.name" />
        </div>
        <div>
            <Input label="البريد الإلكتروني" type="email" v-model="form.email" required autocomplete="username" />
            <FieldError :message="form.errors.email" />
        </div>
        <Alert v-if="mustVerifyEmail && user.email_verified_at === null" variant="warning" title="بريدك غير موثّق">
            <Link :href="route('verification.send')" method="post" as="button" class="font-semibold text-primary hover:underline">
                أعد إرسال رابط التوثيق
            </Link>
            <span v-show="status === 'verification-link-sent'" class="mt-1 block font-medium text-success">تم إرسال رابط جديد.</span>
        </Alert>
        <div class="flex items-center gap-3">
            <Button type="submit" :loading="form.processing">حفظ</Button>
            <Transition enter-active-class="transition" enter-from-class="opacity-0" leave-active-class="transition" leave-to-class="opacity-0">
                <p v-if="form.recentlySuccessful" class="text-sm text-success">تم الحفظ.</p>
            </Transition>
        </div>
    </form>
</template>
