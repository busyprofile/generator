<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import {Head, useForm, usePage} from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import NumberInput from "@/Components/NumberInput.vue";
import InputHelper from "@/Components/InputHelper.vue";
import DropDownWithCheckbox from "@/Components/Form/DropDownWithCheckbox.vue";
import DropDownWithRadio from "@/Components/Form/DropDownWithRadio.vue";
import TextInputBlock from "@/Components/Form/TextInputBlock.vue";
import {ref, watch} from "vue";
import TextInput from "@/Components/TextInput.vue";
import Dropzone from "@/Components/Form/Dropzone.vue";
import SecondaryPageSection from "@/Wrappers/SecondaryPageSection.vue";

const currencies = usePage().props.currencies;
const detail_types = usePage().props.detailTypes;
const payment_gateways = usePage().props.paymentGateways;
const primeTimeCommissionRate = usePage().props.primeTimeCommissionRate;

const form = useForm({
    name: null,
    code: null,
    nspk_schema: null,
    min_limit: null,
    max_limit: null,
    trader_commission_rate_for_orders: 7,
    trader_commission_rate_for_payouts: 1,
    total_service_commission_rate_for_orders: 10,
    total_service_commission_rate_for_payouts: 1,
    is_active: true,
    is_intrabank: false,
    is_transgran: false,
    reservation_time_for_orders: null,
    reservation_time_for_payouts: 1,
    currency: 'RUB',
    detail_types: [],
    sms_senders: [],
    logo: null
});

const submit = () => {
    form.post(route('admin.payment-gateways.store'), {
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

defineOptions({ layout: AuthenticatedLayout })
</script>

<template>
    <div>
        <Head title="Создание платежного метода" />

        <SecondaryPageSection
            :back-link="route('admin.payment-gateways.index')"
            title="Создание платежного метода"
            description="Здесь вы можете создать новый платежный метод."
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
                        <InputHelper v-if="! form.errors.max_limit" model-value="Максимальный лимит на одну операцию"></InputHelper>
                    </div>
                </div>

                <div class="grid md:grid-cols-2 grid-cols-1 gap-6">
                    <div>
                        <InputLabel
                            for="trader_commission_rate_for_orders"
                            value="Комиссия трейдера на сделки в %"
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
                        <InputHelper v-if="! form.errors.trader_commission_rate_for_orders" model-value="Не может быть больше чем комиссия сервиса. Пожалуйста учитывайте прайм-тайм, который будет сложен с комиссией трейдера."></InputHelper>
                    </div>
                    <div>
                        <InputLabel
                            for="total_service_commission_rate_for_orders"
                            value="Полная комиссия сервиса на сделки в %"
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
                        <InputHelper v-if="! form.errors.total_service_commission_rate_for_orders" model-value="Полная комиссия в % которую берет сервис от мерчанта за сделки. Накладывается на USDT сумму после конвертации."></InputHelper>
                    </div>
                </div>

                <div class="text-gray-900 dark:text-gray-200 italic">
                    <div><span class="text-gray-500 dark:text-gray-400 text-sm">Не прайм-тайм:</span> {{ form.total_service_commission_rate_for_orders }}% - {{ form.trader_commission_rate_for_orders }}% = {{ form.total_service_commission_rate_for_orders - form.trader_commission_rate_for_orders }}% <span class="text-gray-500 dark:text-gray-400 text-sm">(доход сервиса)</span></div>
                    <div><span class="text-gray-500 dark:text-gray-400 text-sm">В прайм-тайм:</span>: {{ form.total_service_commission_rate_for_orders }}% - {{ form.trader_commission_rate_for_orders }}% - {{ primeTimeCommissionRate }}% = {{ form.total_service_commission_rate_for_orders - form.trader_commission_rate_for_orders - primeTimeCommissionRate }}% <span class="text-gray-500 dark:text-gray-400 text-sm">(доход сервиса)</span></div>
                </div>

<!--                <div class="grid md:grid-cols-2 grid-cols-1 gap-6">
                    <div>
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
                    </div>
                    <div>
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
                    </div>
                </div>-->

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

                <div>
                    <label class="inline-flex items-center mb-3 mt-3 cursor-pointer">
                        <input type="checkbox" value="" class="sr-only peer" v-model="form.is_active">
                        <div class="relative w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:w-5 after:h-5 after:transition-all dark:border-gray-600 peer-checked:bg-blue-600"></div>
                        <span class="ms-3 text-sm font-medium text-gray-900 dark:text-gray-300">Метод активен</span>
                    </label>
                </div>

                <div>
                    <label class="inline-flex items-center mb-3 mt-3 cursor-pointer">
                        <input type="checkbox" value="" class="sr-only peer" v-model="form.is_intrabank">
                        <div class="relative w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:w-5 after:h-5 after:transition-all dark:border-gray-600 peer-checked:bg-blue-600"></div>
                        <span class="ms-3 text-sm font-medium text-gray-900 dark:text-gray-300">Внутри банковский перевод</span>
                    </label>
                </div>

                <div>
                    <label class="inline-flex items-center mb-3 mt-3 cursor-pointer">
                        <input type="checkbox" value="" class="sr-only peer" v-model="form.is_transgran">
                        <div class="relative w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:w-5 after:h-5 after:transition-all dark:border-gray-600 peer-checked:bg-blue-600"></div>
                        <span class="ms-3 text-sm font-medium text-gray-900 dark:text-gray-300">Трансгран</span>
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
