<script setup>
import Button from '@/Components/ui/Button.vue';
import Input from '@/Components/ui/Input.vue';
import Label from '@/Components/ui/Label.vue';
import { useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const passwordInput = ref(null);
const currentPasswordInput = ref(null);
const form = useForm({ current_password: '', password: '', password_confirmation: '' });

const updatePassword = () => {
    form.put(route('password.update'), {
        preserveScroll: true,
        onSuccess: () => form.reset(),
        onError: () => {
            if (form.errors.password) { form.reset('password', 'password_confirmation'); passwordInput.value?.focus(); }
            if (form.errors.current_password) { form.reset('current_password'); currentPasswordInput.value?.focus(); }
        },
    });
};
</script>

<template>
    <form @submit.prevent="updatePassword" class="space-y-4">
        <div>
            <Input label="كلمة المرور الحالية" ref="currentPasswordInput" v-model="form.current_password" type="password" autocomplete="current-password" />
            <p v-if="form.errors.current_password" class="mt-1 text-xs text-destructive">{{ form.errors.current_password }}</p>
        </div>
        <div>
            <Input label="كلمة المرور الجديدة" ref="passwordInput" v-model="form.password" type="password" autocomplete="new-password" />
            <p v-if="form.errors.password" class="mt-1 text-xs text-destructive">{{ form.errors.password }}</p>
        </div>
        <div>
            <Input label="تأكيد كلمة المرور" v-model="form.password_confirmation" type="password" autocomplete="new-password" />
            <p v-if="form.errors.password_confirmation" class="mt-1 text-xs text-destructive">{{ form.errors.password_confirmation }}</p>
        </div>
        <div class="flex items-center gap-3">
            <Button type="submit" :disabled="form.processing">حفظ</Button>
            <Transition enter-active-class="transition" enter-from-class="opacity-0" leave-active-class="transition" leave-to-class="opacity-0">
                <p v-if="form.recentlySuccessful" class="text-sm text-success">تم الحفظ.</p>
            </Transition>
        </div>
    </form>
</template>
