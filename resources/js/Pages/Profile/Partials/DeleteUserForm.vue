<script setup>
import Button from '@/Components/ui/Button.vue';
import Input from '@/Components/ui/Input.vue';
import Label from '@/Components/ui/Label.vue';
import Dialog from '@/Components/ui/Dialog.vue';
import { useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const confirmingUserDeletion = ref(false);
const passwordInput = ref(null);
const form = useForm({ password: '' });

const deleteUser = () => {
    form.delete(route('profile.destroy'), {
        preserveScroll: true,
        onSuccess: () => (confirmingUserDeletion.value = false),
        onError: () => passwordInput.value?.focus(),
        onFinish: () => form.reset(),
    });
};
</script>

<template>
    <div>
        <Button variant="destructive" @click="confirmingUserDeletion = true">حذف الحساب</Button>

        <Dialog v-model:open="confirmingUserDeletion" title="تأكيد حذف الحساب"
            description="سيتم حذف حسابك وجميع بياناته نهائيًا. أدخل كلمة المرور للتأكيد.">
            <form @submit.prevent="deleteUser" class="space-y-3">
                <div>
                    <Label for="del_password">كلمة المرور</Label>
                    <Input id="del_password" ref="passwordInput" v-model="form.password" type="password" class="mt-1.5" placeholder="••••••••" />
                    <p v-if="form.errors.password" class="mt-1 text-xs text-destructive">{{ form.errors.password }}</p>
                </div>
                <div class="flex justify-end gap-2">
                    <Button type="button" variant="outline" @click="confirmingUserDeletion = false">إلغاء</Button>
                    <Button type="submit" variant="destructive" :disabled="form.processing">حذف نهائيًا</Button>
                </div>
            </form>
        </Dialog>
    </div>
</template>
