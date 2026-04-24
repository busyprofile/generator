<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import TextInput from "@/Components/TextInput.vue";
import SecondaryPageSection from "@/Wrappers/SecondaryPageSection.vue";
import Multiselect from "@/Components/Form/Multiselect.vue";
import { ref } from 'vue';

const support = usePage().props.support;
const merchants = usePage().props.merchants;
const supportMerchantIds = usePage().props.supportMerchantIds || [];

const form = useForm({
    name: support.name,
    email: support.email,
    banned: !!support.banned_at,
    merchant_ids: supportMerchantIds,
});

const submit = () => {
    form.patch(route('merchant.support.update', support.id), {
        preserveScroll: true
    });
};

defineOptions({ layout: AuthenticatedLayout })
</script>

<template>
    <div>
        <Head title="Редактирование саппорта" />

        <SecondaryPageSection
            :back-link="route('merchant.support.index')"
            title="Редактирование саппорта"
            description="Здесь вы можете изменить данные сотрудника поддержки."
        >
            <form @submit.prevent="submit" class="mt-6 space-y-6">
                <div>
                    <InputLabel
                        for="name"
                        value="Имя"
                        :error="!!form.errors.name"
                    />

                    <TextInput
                        id="name"
                        class="mt-1 block w-full"
                        v-model="form.name"
                        required
                        autofocus
                        autocomplete="name"
                        :error="!!form.errors.name"
                        @input="form.clearErrors('name')"
                    />

                    <InputError class="mt-2" :message="form.errors.name" />
                </div>

                <div>
                    <InputLabel
                        for="email"
                        value="Почта"
                        :error="!!form.errors.email"
                    />

                    <TextInput
                        id="email"
                        type="email"
                        class="mt-1 block w-full"
                        v-model="form.email"
                        required
                        autocomplete="username"
                        :error="!!form.errors.email"
                        @input="form.clearErrors('email')"
                    />

                    <InputError class="mt-2" :message="form.errors.email" />
                </div>

                <div>
                    <InputLabel
                        for="merchant_ids"
                        value="Доступные мерчанты"
                        :error="!!form.errors.merchant_ids"
                    />

                    <Multiselect
                        id="merchant_ids"
                        v-model="form.merchant_ids"
                        :options="merchants"
                        label-key="label"
                        value-key="value"
                        :enable-search="true"
                        placeholder="Выберите доступные мерчанты"
                        @input="form.clearErrors('merchant_ids')"
                    />

                    <InputError class="mt-2" :message="form.errors.merchant_ids" />
                </div>

                <div class="block">
                    <label class="flex items-center">
                        <input
                            type="checkbox"
                            class="rounded dark:bg-gray-900 border-gray-300 dark:border-gray-700 text-indigo-600 shadow-sm focus:ring-indigo-500 dark:focus:ring-indigo-600 dark:focus:ring-offset-gray-800"
                            v-model="form.banned"
                        >
                        <span class="ml-2 text-sm text-gray-600 dark:text-gray-400">Заблокировать</span>
                    </label>
                </div>

                <div class="flex items-center gap-4">
                    <PrimaryButton :disabled="form.processing">Сохранить</PrimaryButton>

                    <Transition
                        enter-active-class="transition ease-in-out"
                        enter-from-class="opacity-0"
                        leave-active-class="transition ease-in-out"
                        leave-to-class="opacity-0"
                    >
                        <p v-if="form.recentlySuccessful" class="text-sm text-gray-600 dark:text-gray-400">Сохранено.</p>
                    </Transition>
                </div>
            </form>
        </SecondaryPageSection>
    </div>
</template>
