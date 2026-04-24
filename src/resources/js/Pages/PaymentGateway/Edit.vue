<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import {Head, router, useForm, usePage} from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import NumberInput from "@/Components/NumberInput.vue";
import InputHelper from "@/Components/InputHelper.vue";
import GoBackButton from "@/Components/GoBackButton.vue";
import TextInputBlock from "@/Components/Form/TextInputBlock.vue";
import DropDownWithRadio from "@/Components/Form/DropDownWithRadio.vue";
import DropDownWithCheckbox from "@/Components/Form/DropDownWithCheckbox.vue";
import {ref, watch} from "vue";
import TextInput from "@/Components/TextInput.vue";
import Dropzone from "@/Components/Form/Dropzone.vue";
import SecondaryPageSection from "@/Wrappers/SecondaryPageSection.vue";
import Button from 'primevue/button';

const payment_gateway = usePage().props.paymentGateway;
const currencies = usePage().props.currencies;
const detail_types = usePage().props.detailTypes;
const payment_gateways = usePage().props.paymentGateways;

const form = useForm({
    name: payment_gateway.original_name,
    code: payment_gateway.code,
    nspk_schema: payment_gateway.nspk_schema,
    min_limit: payment_gateway.min_limit,
    max_limit: payment_gateway.max_limit,
    trader_commission_rate_for_orders: payment_gateway.trader_commission_rate_for_orders,
    trader_commission_rate_for_payouts: payment_gateway.trader_commission_rate_for_payouts,
    total_service_commission_rate_for_orders: payment_gateway.total_service_commission_rate_for_orders,
    total_service_commission_rate_for_payouts: payment_gateway.total_service_commission_rate_for_payouts,
    is_active: !!payment_gateway.is_active,
    is_intrabank: !!payment_gateway.is_intrabank,
    is_transgran: !!payment_gateway.is_transgran,
    reservation_time_for_orders: payment_gateway.reservation_time_for_orders,
    reservation_time_for_payouts: payment_gateway.reservation_time_for_payouts,
    currency: payment_gateway.currency.toUpperCase(),
    detail_types: payment_gateway.detail_types ?? [],
    sms_senders: payment_gateway.sms_senders ?? [],
    logo: null,
    _method: 'PATCH'
});
const submit = () => {

    form.post(route('admin.payment-gateways.update', payment_gateway.id), {
        preserveScroll: true,
        onSuccess: () => form.reset(),
    });
};

const sms_sender = ref(null);

const addSender = () => {
    if (! sms_sender.value) {
        return;
    }

    form.sms_senders.push(sms_sender.value)

    form.sms_senders = form.sms_senders.filter((value, index, array) => {
        return array.indexOf(value) === index;
    });

    sms_sender.value = null;
}

const removeSender = (sender) => {
    form.sms_senders = form.sms_senders.filter((item) => {
        return item !== sender
    });
}

// Смотрим за изменением is_intrabank
watch(() => form.is_intrabank, (newValue) => {
    if (newValue) {
        // Если включен внутрибанковский перевод, удаляем тип "phone" из detail_types
        form.detail_types = form.detail_types.filter(type => type !== 'phone');
    }
});

// Добавляем функцию для переключения is_intrabank, если она не заблокирована
const toggleIntrabank = () => {
    if (!payment_gateway.is_intrabank) { // Разрешаем переключение только если изначально было false
        form.is_intrabank = !form.is_intrabank;
    }
};

defineOptions({ layout: AuthenticatedLayout })
</script>

<template>
    <div>
        <Head title="Редактирование платежного метода" />

        <SecondaryPageSection
            :back-link="route('admin.payment-gateways.index')"
            title="Редактирование платежного метода"
            description="Здесь вы можете отредактировать платежный метод."
        >
            <form @submit.prevent="submit" class="mt-6 space-y-6">
                <TextInputBlock
                    v-model="form.name"
                    :form="form"
                    field="name"
                    label="Название"
                    placeholder="Сбербанк"
                />

                <div class="grid md:grid-cols-2 grid-cols-1 gap-6">
                    <TextInputBlock
                        v-model="form.code"
                        :form="form"
                        field="code"
                        label="Код метода"
                        helper="Например: sberbank"
                    />

                    <TextInputBlock
                        v-model="form.nspk_schema"
                        :form="form"
                        field="nspk_schema"
                        label="Scheme"
                        helper="Например: 100000000111"
                    />
                </div>

                <div>
                    <DropDownWithCheckbox
                        v-model="form.detail_types"
                        :items="detail_types.filter(type => !form.is_intrabank || type.code !== 'phone')"
                        value="code"
                        name="name"
                        label="Тип реквизитов"
                    />
                    <InputError :message="form.errors.detail_types" class="mt-2" />
                    <InputHelper v-if="form.is_intrabank" model-value="Тип 'Телефон' недоступен для внутрибанковского перевода"></InputHelper>
                </div>

                <div>
                    <DropDownWithRadio
                        v-model="form.currency"
                        :items="currencies"
                        value="code"
                        name="code"
                        label="Валюта"
                    />
                    <InputError :message="form.errors.currency" class="mt-2" />
                </div>

                <div class="grid md:grid-cols-2 grid-cols-1 gap-6">
                    <div>
                        <InputLabel
                            for="min_limit"
                            :value="'Минимальная сумма в ' + form.currency?.toUpperCase()"
                            :error="!!form.errors.min_limit"
                        />

                        <NumberInput
                            id="min_limit"
                            v-model="form.min_limit"
                            class="mt-1 block w-full"
                            placeholder="0"
                            :error="!!form.errors.min_limit"
                            @input="form.clearErrors('min_limit')"
                        />

                        <InputError :message="form.errors.min_limit" class="mt-2" />
                        <InputHelper v-if="! form.errors.min_limit" model-value="Минимальный лимит на одну операцию"></InputHelper>
                    </div>

                    <div>
                    <InputLabel
                        for="max_limit"
                        :value="'Максимальная сумма в ' + form.currency?.toUpperCase()"
                        :error="!!form.errors.max_limit"
                    />

                    <NumberInput
                        id="max_limit"
                        v-model="form.max_limit"
                        class="mt-1 block w-full"
                        placeholder="0"
                        :error="!!form.errors.max_limit"
                        @input="form.clearErrors('max_limit')"
                    />

                    <InputError :message="form.errors.max_limit" class="mt-2" />
                    <InputHelper v-if="! form.errors.max_limit" model-value="Минимальный лимит на одну операцию"></InputHelper>
                </div>
                </div>

                <div class="grid md:grid-cols-1 grid-cols-1 gap-6">
                    <div>
                        <InputLabel
                            for="trader_commission_rate_for_orders"
                            value="Наценка на покупку USDT % (вход)"
                            :error="!!form.errors.trader_commission_rate_for_orders"
                        />

                        <NumberInput
                            id="trader_commission_rate_for_orders"
                            v-model="form.trader_commission_rate_for_orders"
                            class="mt-1 block w-full"
                            step="0.1"
                            placeholder="0.0"
                            :error="!!form.errors.trader_commission_rate_for_orders"
                            @input="form.clearErrors('trader_commission_rate_for_orders')"
                        />

                        <InputError :message="form.errors.trader_commission_rate_for_orders" class="mt-2" />
                        <InputHelper v-if="! form.errors.trader_commission_rate_for_orders" model-value="Наценка на курс покупки USDT в %, которую забирает себе трейдер"></InputHelper>
                    </div>

<!--                    <div>
                        <InputLabel
                            for="trader_commission_rate_for_payouts"
                            value="Наценка на продажу USDT % (выход)"
                            :error="!!form.errors.trader_commission_rate_for_payouts"
                        />

                        <NumberInput
                            id="trader_commission_rate_for_payouts"
                            v-model="form.trader_commission_rate_for_payouts"
                            class="mt-1 block w-full"
                            step="0.1"
                            placeholder="0.0"
                            :error="!!form.errors.trader_commission_rate_for_payouts"
                            @input="form.clearErrors('trader_commission_rate_for_payouts')"
                        />

                        <InputError :message="form.errors.trader_commission_rate_for_payouts" class="mt-2" />
                        <InputHelper v-if="! form.errors.trader_commission_rate_for_payouts" model-value="Наценка на курс продажи USDT в %, которую забирает себе трейдер"></InputHelper>
                    </div>-->
                </div>

                <div class="grid md:grid-cols-1 grid-cols-1 gap-6">
                    <div>
                        <InputLabel
                            for="total_service_commission_rate_for_orders"
                            value="Комиссия сервиса на сделки в %"
                            :error="!!form.errors.total_service_commission_rate_for_orders"
                        />

                        <NumberInput
                            id="total_service_commission_rate_for_orders"
                            v-model="form.total_service_commission_rate_for_orders"
                            class="mt-1 block w-full"
                            step="0.1"
                            placeholder="0.0"
                            :error="!!form.errors.total_service_commission_rate_for_orders"
                            @input="form.clearErrors('total_service_commission_rate_for_orders')"
                        />

                        <InputError :message="form.errors.total_service_commission_rate_for_orders" class="mt-2" />
                        <InputHelper v-if="! form.errors.total_service_commission_rate_for_orders" model-value="Наценка в % на базовую сумму сделки, которую забирает себе сервис."></InputHelper>
                    </div>

<!--                    <div>
                        <InputLabel
                            for="total_service_commission_rate_for_payouts"
                            value="Комиссия сервиса на выплаты в %"
                            :error="!!form.errors.total_service_commission_rate_for_payouts"
                        />

                        <NumberInput
                            id="total_service_commission_rate_for_payouts"
                            v-model="form.total_service_commission_rate_for_payouts"
                            class="mt-1 block w-full"
                            step="0.1"
                            placeholder="0.0"
                            :error="!!form.errors.total_service_commission_rate_for_payouts"
                            @input="form.clearErrors('total_service_commission_rate_for_payouts')"
                        />

                        <InputError :message="form.errors.total_service_commission_rate_for_payouts" class="mt-2" />
                        <InputHelper v-if="! form.errors.total_service_commission_rate_for_payouts" model-value="Наценка в % на базовую сумму выплаты, которую забирает себе сервис."></InputHelper>
                    </div>-->
                </div>

                <div class="grid md:grid-cols-1 grid-cols-1 gap-6">
                    <div>
                        <InputLabel
                            for="reservation_time_for_orders"
                            value="Время удержания реквизитов"
                            :error="!!form.errors.reservation_time_for_orders"
                        />

                        <NumberInput
                            id="reservation_time_for_orders"
                            v-model="form.reservation_time_for_orders"
                            class="mt-1 block w-full"
                            placeholder="0"
                            :error="!!form.errors.reservation_time_for_orders"
                            @input="form.clearErrors('reservation_time_for_orders')"
                        />

                        <InputError :message="form.errors.reservation_time_for_orders" class="mt-2" />
                        <InputHelper v-if="! form.errors.reservation_time_for_orders" model-value="Время на одну операцию обмена в минутах"></InputHelper>
                    </div>

<!--                    <div>
                        <InputLabel
                            for="reservation_time_for_payouts"
                            value="Время на проведение выплаты"
                            :error="!!form.errors.reservation_time_for_payouts"
                        />

                        <NumberInput
                            id="reservation_time_for_payouts"
                            v-model="form.reservation_time_for_payouts"
                            class="mt-1 block w-full"
                            placeholder="0"
                            :error="!!form.errors.reservation_time_for_payouts"
                            @input="form.clearErrors('reservation_time_for_payouts')"
                        />

                        <InputError :message="form.errors.reservation_time_for_payouts" class="mt-2" />
                        <InputHelper v-if="! form.errors.reservation_time_for_payouts" model-value="Время за которое нужно завершить выплату в минутах"></InputHelper>
                    </div>-->
                </div>

                <div>
                    <InputLabel
                        for="sms_senders"
                        value="Отправители смс/push"
                        :error="!!form.errors.sms_senders"
                        class="mb-1"
                    />

                    <div class="relative">
                        <TextInput
                            id="sms_senders"
                            v-model="sms_sender"
                            class="block w-full"
                            :error="!!form.errors.sms_senders"
                            @input="form.clearErrors('sms_senders')"
                        />

                        <button @click.prevent="addSender" type="button" class="text-white absolute end-1.5 sm:bottom-1 bottom-1.5 bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-xl  text-sm sm:px-3 sm:py-1.5 px-2 py-1 dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800">Добавить</button>
                    </div>

                    <InputError :message="form.errors.sms_senders" class="mt-2" />
                    <InputHelper v-if="! form.errors.sms_senders" model-value="Например: 900, Alfabank"></InputHelper>

                    <div class="flex flex-wrap gap-0.5 mt-2">
                        <div v-for="sender in form.sms_senders">
                                        <span class="bg-gray-100 text-gray-800 text-xs font-medium inline-flex items-center px-2.5 py-0.5 rounded me-2 dark:bg-gray-700 dark:text-gray-400 border border-gray-500">
                                            {{ sender }}
                                            <svg @click="removeSender(sender)" class="w-2.5 h-2.5 ml-1.5 cursor-pointer" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18 17.94 6M18 18 6.06 6"/>
                                            </svg>
                                        </span>
                        </div>
                    </div>
                </div>

                <div>
                    <InputLabel
                        for="logo"
                        value="Загрузите логотип метода"
                        class="mb-1"
                        :error="!!form.errors.reservation_time_for_orders"
                    />
                    <Dropzone
                        v-model="form.logo"
                        title="Нажмите, чтобы загрузить изображение"
                        description="PNG (квадрат 1x1)"
                    />
                    <InputError :message="form.errors.logo" class="mt-2" />
                </div>

                <div v-if="payment_gateway.logo_path">
                    <img :src="payment_gateway.logo_path" class="w-20">
                </div>

                <div class="grid md:grid-cols-3 grid-cols-1 gap-6 pt-4">
                    <div>
                        <InputLabel value="Метод активен" class="mb-1" />
                        <Button
                            :label="form.is_active ? 'Активен' : 'Не активен'"
                            :icon="form.is_active ? 'pi pi-check-circle' : 'pi pi-ban'"
                            :severity="form.is_active ? 'success' : 'danger'"
                            @click="form.is_active = !form.is_active"
                            class="p-button-sm w-full mt-1"
                            outlined
                        />
                         <InputError :message="form.errors.is_active" class="mt-2" />
                    </div>

                    <div>
                        <InputLabel value="Внутрибанковский перевод" class="mb-1" />
                        <Button
                            :label="form.is_intrabank ? 'Включен' : 'Выключен'"
                            :icon="form.is_intrabank ? 'pi pi-check-circle' : 'pi pi-ban'"
                            :severity="form.is_intrabank ? 'success' : 'danger'"
                            @click="toggleIntrabank"
                            class="p-button-sm w-full mt-1"
                            :disabled="payment_gateway.is_intrabank"
                            outlined
                        />
                        <InputHelper v-if="payment_gateway.is_intrabank" class="mt-1 text-xs text-red-500" model-value="(нельзя отключить после активации)"></InputHelper>
                        <InputError :message="form.errors.is_intrabank" class="mt-2" />
                    </div>

                    <div>
                        <InputLabel value="Трансгран" class="mb-1" />
                        <Button
                            :label="form.is_transgran ? 'Включен' : 'Выключен'"
                            :icon="form.is_transgran ? 'pi pi-check-circle' : 'pi pi-ban'"
                            :severity="form.is_transgran ? 'success' : 'danger'"
                            @click="form.is_transgran = !form.is_transgran"
                            class="p-button-sm w-full mt-1"
                            outlined
                        />
                        <InputError :message="form.errors.is_transgran" class="mt-2" />
                    </div>
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
