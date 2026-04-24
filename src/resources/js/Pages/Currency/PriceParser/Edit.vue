<script setup>
import PrimaryButton from '@/Components/PrimaryButton.vue';
import {Head, useForm, usePage} from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputLabel from "@/Components/InputLabel.vue";
import InputError from "@/Components/InputError.vue";
import InputHelper from "@/Components/InputHelper.vue";
import Select from "@/Components/Select.vue";
import NumberInput from "@/Components/NumberInput.vue";
import SecondaryPageSection from "@/Wrappers/SecondaryPageSection.vue";

const currency = usePage().props.currency.toUpperCase();
const methods = usePage().props.methods;
const settings = usePage().props.settings;

const form = useForm({
    amount: settings.amount,
    payment_method: settings.payment_method ?? 0,
    ad_quantity: settings.ad_quantity
});

const submit = () => {
    form
        .transform((data) => {
            if (data.payment_method === 0) {
                data.payment_gateway_id = null;
            }

            return data;
        })
        .patch(route('admin.currencies.price-parsers.update', currency.toLowerCase()), {
            preserveScroll: true,
            onError: () => form.reset(),
        });
};

defineOptions({ layout: AuthenticatedLayout })
</script>

<template>
    <div>
        <Head :title="'Настройка парсера для валюты - ' + currency" />

        <SecondaryPageSection
            :back-link="route('admin.currencies.index')"
            :title="'Настройка парсера для валюты - ' + currency"
            description="Здесь вы можете настроить парсер цен на обмен USDT для выбранной валюты."
        >
            <form @submit.prevent="submit" class="mt-6 space-y-6">
                <div class="flex items-center p-4 mb-4 text-sm text-blue-800 border border-blue-300 rounded-xl  bg-blue-50 dark:bg-gray-800 dark:text-blue-400 dark:border-blue-800" role="alert">
                    <svg class="flex-shrink-0 inline w-4 h-4 me-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5ZM9.5 4a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3ZM12 15H8a1 1 0 0 1 0-2h1v-3H8a1 1 0 0 1 0-2h2a1 1 0 0 1 1 1v4h1a1 1 0 0 1 0 2Z"/>
                    </svg>
                    <span class="sr-only">Информация</span>
                    <div>
                        Данные настройки только для ByBit P2P парсера.
                    </div>
                </div>
                <div>
                    <InputLabel
                        for="amount"
                        :value="'Сумма в ' + currency"
                        :error="!!form.errors.amount"
                    />

                    <NumberInput
                        id="amount"
                        v-model="form.amount"
                        type="text"
                        class="mt-1 block w-full"
                        :error="!!form.errors.amount"
                        @input="form.clearErrors('amount')"
                        placeholder="Введите сумму"
                    />

                    <InputError :message="form.errors.amount" class="mt-2" />
                    <InputHelper v-if="! form.errors.amount" model-value="Минимальный сумма доступного лимита на обмен"></InputHelper>
                </div>

                <div>
                    <InputLabel
                        for="payment_method"
                        value="Платежный метод"
                        :error="!!form.errors.payment_method"
                        class="mb-1"
                    />
                    <Select
                        id="payment_method"
                        v-model="form.payment_method"
                        :error="!!form.errors.payment_method"
                        :items="methods"
                        value="id"
                        name="name"
                        default_title="Выберите платежный метод"
                        @change="form.clearErrors('payment_method');"
                    ></Select>

                    <InputError :message="form.errors.payment_method" class="mt-2" />
                </div>

                <div>
                    <InputLabel
                        for="ad_quantity"
                        value="Количество объявлений"
                        :error="!!form.errors.ad_quantity"
                    />

                    <NumberInput
                        id="ad_quantity"
                        v-model="form.ad_quantity"
                        type="text"
                        class="mt-1 block w-full"
                        :error="!!form.errors.ad_quantity"
                        @input="form.clearErrors('ad_quantity')"
                        placeholder="Укажите количество объявлений"
                    />

                    <InputError :message="form.errors.ad_quantity" class="mt-2" />
                    <InputHelper v-if="! form.errors.ad_quantity" model-value="Парсер возьмет первые N количество объявлений, и рассчитает усредненную цену."></InputHelper>
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
