<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, useForm } from '@inertiajs/vue3';

defineProps({
    status: {
        type: String,
    },
});

const form = useForm({
    email: '',
});

const submit = () => {
    form.post(route('admin.password.email'));
};
</script>

<template>
    <GuestLayout>
        <Head title="Забыли пароль" />

        <div class="mb-4 text-sm text-gray-600 dark:text-gray-400">
            Забыли пароль? Не беда. Просто сообщите нам свой адрес электронной почты, и мы пришлем вам ссылку для сброса пароля
            ссылку, по которой вы сможете выбрать новый.
        </div>

        <div v-if="status" class="mb-4 font-medium text-sm text-green-600 dark:text-green-400">
            {{ status }}
        </div>

        <form @submit.prevent="submit">
            <div>
                <InputLabel for="email" value="Почта" />

                <TextInput
                    id="email"
                    type="email"
                    class="mt-1 block w-full"
                    v-model="form.email"
                    required
                    autofocus
                    autocomplete="username"
                />

                <InputError class="mt-2" :message="form.errors.email" />
            </div>

            <div class="flex items-center justify-end mt-4">
                <PrimaryButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                    Отправить ссылку
                </PrimaryButton>
            </div>
        </form>
    </GuestLayout>
</template>
