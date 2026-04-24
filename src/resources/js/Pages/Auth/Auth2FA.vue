<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { Head, useForm } from '@inertiajs/vue3';
import Code2FA from "@/Pages/Auth/Components/Code2FA.vue";
import InputError from "@/Components/InputError.vue";

const form = useForm({
    one_time_password: ''
});

const submit = () => {
    form.post(route('auth.2fa.validate'), {
        onBefore: () => form.reset('one_time_password'),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Введите 2FA Код" />

        <form @submit.prevent="submit" class="my-3">
            <div class="mb-6">
                <h2 class="text-2xl font-semibold text-gray-900 dark:text-gray-200 text-center mb-5">Введите 2FA Код</h2>

                <Code2FA v-model="form.one_time_password"/>
                <div class="flex justify-center">
                    <InputError class="mt-2" :message="form.errors.one_time_password" />
                </div>
                <p v-if="$page.props.flash.error" class="mt-2 text-sm text-red-700 dark:text-red-500 text-center">
                    {{ $page.props.flash.error }}
                </p>
            </div>

            <div class="flex items-center justify-center mt-4">
                <PrimaryButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                    Войти
                </PrimaryButton>
            </div>
        </form>
    </GuestLayout>
</template>
