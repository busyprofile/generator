<script setup>
import TextInput from "@/Components/TextInput.vue";
import InputError from "@/Components/InputError.vue";
import InputLabel from "@/Components/InputLabel.vue";
import SaveButton from "@/Components/Form/SaveButton.vue";
import {useForm, usePage} from "@inertiajs/vue3";
import {onMounted, ref} from "vue";
import CopyUUID from "@/Components/CopyUUID.vue";
import {useViewStore} from "@/store/view.js";
import Select from "@/Components/Select.vue";
import InputHelper from "@/Components/InputHelper.vue";
import Multiselect from "@/Components/Form/Multiselect.vue";
import ExchangeRateMarkup from "@/Pages/Merchant/AdminSetting/ExchangeRateMarkup.vue";

const viewStore = useViewStore();

const merchant = ref(usePage().props.merchant);
const markets = ref(usePage().props.markets);
const paymentGateways = usePage().props.paymentGateways;
const gatewaySettings = ref(usePage().props.merchant.gateway_settings);

const formCallback = useForm({
    callback_url: merchant.value.callback_url,
});
const formCommission = useForm({
    gateway_settings: null,
});
const formSettings = useForm({
    market: merchant.value.market,
});

const formStatus = useForm({});

const gatewayEditMode = ref(false);

const groupedGateways = ref(null);

const macros = ref({
    commission: null,
    reservation_time: null,
});

const submitCallback = () => {
    formCallback.patch(route('merchants.callback.update', merchant.value.id), {
        preserveScroll: true,
    });
};

const submitSettings = () => {
    formSettings.patch(route('admin.merchants.settings.update', merchant.value.id), {
        preserveScroll: true,
    });
};

const submitGatewaySettings = () => {
    formCommission
        .transform((data) => {
            data.gateway_settings = gatewaySettings.value;

            return data;
        })
        .patch(route('merchants.gateway-settings.update', merchant.value.id), {
            preserveScroll: true,
        });
};

const submitBan = () => {
    formStatus.patch(route('admin.merchants.ban', merchant.value.id), {
        preserveScroll: true,
        onSuccess: (result) => {
            merchant.value = result.props.merchant;
        },
    });
};
const submitUnban = () => {
    formStatus.patch(route('admin.merchants.unban', merchant.value.id), {
        preserveScroll: true,
        onSuccess: (result) => {
            merchant.value = result.props.merchant;
        },
    });
};

const submitValidated = () => {
    formStatus.patch(route('admin.merchants.validated', merchant.value.id), {
        preserveScroll: true,
        onSuccess: (result) => {
            merchant.value = result.props.merchant;
        },
    });
};

const normalizeValue = (value, min = 1, max = 1000) => {
    if (value === "" || value === null || value === undefined) {
        return null;
    }

    let num = Number(value);

    if (isNaN(num)) {
        return min;
    }

    return Math.min(Math.max(num, min), max);
}

const setCustomGatewayCommission = (settings, originalCommission, commission) => {
    if (! commission) {
        return;
    }

    if (parseFloat(commission) > 100) {
        settings['custom_gateway_commission'] = 100;
    }
    if (settings['merchant_commission'] > settings['custom_gateway_commission']) {
        settings['merchant_commission'] = settings['custom_gateway_commission'];
    }
    if (settings['merchant_commission'] === null || settings['merchant_commission'] < 0) {
        settings['merchant_commission'] = 0;
    }
    if (originalCommission === settings['custom_gateway_commission']) {
        settings['custom_gateway_commission'] = null;
    }
}

const setCustomReservationTime = (settings, reservationTime) => {
    if (reservationTime === "" || reservationTime === null || reservationTime === undefined) {
        settings['custom_gateway_reservation_time'] = null;
        return;
    }

    let num = Number(reservationTime);

    if (isNaN(num)) {
        settings['custom_gateway_reservation_time'] = 1;
        return;
    }

    settings['custom_gateway_reservation_time'] = Math.min(Math.max(num, 1), 1000);
}

const applyMacros = (type) => {
    if (type === 'commission') {
        for (const key in gatewaySettings.value) {
            gatewaySettings.value[key]['custom_gateway_commission'] = normalizeValue(macros.value.commission, 0, 100);
        }
    }
    if (type === 'reservation_time') {
        for (const key in gatewaySettings.value) {
            gatewaySettings.value[key]['custom_gateway_reservation_time'] = normalizeValue(macros.value.reservation_time);
        }
    }
}


onMounted(() => {
    groupedGateways.value = Object.groupBy(paymentGateways.data, ({ currency }) => currency);
})
</script>

<template>
    <div class="space-y-6">
        <div class="mb-6">
            <div class="gap-8 grid grid-cols-1 2xl:grid-cols-7 xl:grid-cols-5">
                <div class="2xl:col-span-3 xl:col-span-2 space-y-6">
                    <div>
                        <h3 class="mb-3 text-xl font-medium text-gray-900 dark:text-white">Магазин</h3>
                        <ul class="text-sm font-medium shadow-md text-gray-900 bg-white rounded-plate dark:bg-gray-800 dark:text-white">
                            <li class="w-full sm:px-6 px-5 py-3 border-b border-gray-200 gap-5 rounded-t-xl dark:border-gray-700 flex justify-between">
                                <span class="text-gray-900 dark:text-gray-200">Название</span>
                                <span class="text-gray-500 dark:text-gray-400 truncate break-all">
                        {{ merchant.name }}
                    </span>
                            </li>
                            <li class="w-full sm:px-6 px-5 py-3 border-b border-gray-200 gap-5 rounded-t-xl dark:border-gray-700 flex justify-between">
                                <span class="text-gray-900 dark:text-gray-200 col-span-2">Описание</span>
                                <span class="text-gray-500 dark:text-gray-400 col-span-3 text-right break-all">
                        {{ merchant.description }}
                    </span>
                            </li>
                            <li class="w-full sm:px-6 px-5 py-3 border-b border-gray-200 gap-5 rounded-t-xl dark:border-gray-700 flex justify-between">
                                <span class="text-gray-900 dark:text-gray-200">Домен</span>
                                <span class="text-gray-500 dark:text-gray-400 break-all">
                        {{ merchant.domain }}
                    </span>
                            </li>
                            <li class="w-full sm:px-6 px-5 py-3 border-b border-gray-200 rounded-t-xl dark:border-gray-700 flex justify-between">
                                <span class="dark:text-gray-200">Статус</span>
                                <span>
                        <span v-if="merchant.active" class="bg-green-100 text-green-800 text-xs font-medium px-2.5 py-0.5 rounded-xl dark:bg-green-900 dark:text-green-300">Активен</span>
                        <span v-else class="bg-red-100 text-red-800 text-xs font-medium px-2.5 py-0.5 rounded-xl dark:bg-red-900 dark:text-red-300">Остановлен</span>
                    </span>
                            </li>
                            <li v-if="viewStore.isAdminViewMode" class="w-full sm:px-6 px-5 py-3 border-b border-gray-200 rounded-t-xl dark:border-gray-700 flex justify-between">
                                <span class="text-gray-900 dark:text-gray-200">Владелец</span>
                                <span class="text-gray-500 dark:text-gray-400">{{ merchant.owner.email }}</span>
                            </li>
                            <li class="w-full sm:px-6 px-5 py-3 rounded-b-xl flex justify-between">
                                <span class="text-gray-900 dark:text-gray-200">Merchant ID</span>
                                <span class="text-gray-500 dark:text-gray-400">
                        <CopyUUID :text="merchant.uuid"></CopyUUID>
                    </span>
                            </li>
                        </ul>
                    </div>
                    <div v-if="viewStore.isAdminViewMode">
                        <h3 class="text-xl font-medium text-gray-900 dark:text-white mb-3">Модерация</h3>
                        <div class="p-5 sm:p-6 bg-white shadow-md rounded-plate dark:bg-gray-800">
                            <p class="mb-3 text-sm font-medium text-gray-500 dark:text-gray-300">
                                Разрешите работу мерчанта или заблокируйте его.
                            </p>
                            <form @submit.prevent="submitCallback">
                                <div class="flex items-center justify-center">
                                    <h1 class="text-gray-500 dark:text-gray-400 text-sm mr-3">Текущий статус:</h1>
                                    <div class="flex items-center text-nowrap text-gray-900 dark:text-gray-200">
                                        <template v-if="! merchant.validated_at">
                                            <div class="h-2.5 w-2.5 rounded-full bg-primary me-2"></div> На модерации
                                        </template>
                                        <template v-else-if="merchant.banned_at">
                                            <div class="h-2.5 w-2.5 rounded-full bg-red-500 dark:bg-red-500 me-2"></div> Заблокирован
                                        </template>
                                        <template v-else-if="merchant.active">
                                            <div class="h-2.5 w-2.5 rounded-full bg-green-400 dark:bg-green-500 me-2"></div> Включен
                                        </template>
                                        <template v-else>
                                            <div class="h-2.5 w-2.5 rounded-full bg-red-500 dark:bg-red-500 me-2"></div> Выключен
                                        </template>
                                    </div>
                                </div>
                                <div class="flex justify-center mt-3 gap-2">
                                    <button
                                        @click="submitValidated"
                                        v-if="! merchant.validated_at"
                                        type="button"
                                        class="px-3 py-2 text-sm font-medium focus:outline-none text-white bg-green-700 hover:bg-green-800 focus:ring-4 focus:ring-green-300 rounded-xl dark:bg-green-600 dark:hover:bg-green-700 dark:focus:ring-green-800"
                                    >
                                        Разрешить
                                    </button>
                                    <button
                                        @click="submitUnban"
                                        v-if="merchant.banned_at"
                                        type="button"
                                        class="px-3 py-2 text-sm font-medium text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:ring-blue-300 rounded-xl dark:bg-blue-600 dark:hover:bg-blue-700 focus:outline-none dark:focus:ring-blue-800"
                                    >
                                        Разблокировать
                                    </button>
                                    <button
                                        @click="submitBan"
                                        v-else
                                        type="button"
                                        class="px-3 py-2 text-sm font-medium focus:outline-none text-white bg-red-700 hover:bg-red-800 focus:ring-4 focus:ring-red-300 rounded-xl dark:bg-red-600 dark:hover:bg-red-700 dark:focus:ring-red-900"
                                    >
                                        Заблокировать
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                <div class="2xl:col-span-4 xl:col-span-3 space-y-6">
                    <div>
                        <h3 class="text-xl font-medium text-gray-900 dark:text-white mb-3">Обработчик платежей</h3>
                        <div class="p-5 sm:p-6 bg-white shadow-md rounded-plate dark:bg-gray-800">
                            <p class="mb-5 text-sm font-medium text-gray-500 dark:text-gray-300">
                                Установите ссылку на Ваш обработчик для получения уведомлений. По ней мы будем отправлять POST запросы о статусах платежей.
                            </p>
                            <form class="space-y-4" @submit.prevent="submitCallback">
                                <div>
                                    <InputLabel
                                        for="callback_url"
                                        value="Укажите ссылку"
                                        :error="!!formCallback.errors.callback_url"
                                    />

                                    <TextInput
                                        id="callback_url"
                                        v-model="formCallback.callback_url"
                                        type="text"
                                        class="mt-1 block w-full"
                                        placeholder="https://example.com/callback"
                                        :error="!!formCallback.errors.callback_url"
                                        @input="formCallback.clearErrors('callback_url')"
                                    />

                                    <InputError :message="formCallback.errors.callback_url" class="mt-2" />
                                </div>

                                <SaveButton
                                    :disabled="formCallback.processing"
                                    :saved="formCallback.recentlySuccessful"
                                ></SaveButton>
                            </form>
                        </div>
                    </div>
                    <div v-if="viewStore.isAdminViewMode">
                        <h3 class="text-xl font-medium text-gray-900 dark:text-white mb-3">Настройки для администратора</h3>
                        <div class="p-5 sm:p-6 bg-white shadow-md rounded-plate dark:bg-gray-800">
                            <form class="space-y-4" @submit.prevent="submitSettings">
                                <div>
                                    <InputLabel
                                        for="payment_gateway_id"
                                        value="Источник курсов (маркет)"
                                        :error="!!formSettings.errors.market"
                                        class="mb-1"
                                    />
                                    <Select
                                        id="market"
                                        v-model="formSettings.market"
                                        :error="!!formSettings.errors.market"
                                        :items="markets"
                                        value="value"
                                        name="name"
                                        default_title="Выберите маркет"
                                        @change="formSettings.clearErrors('market');"
                                    ></Select>

                                    <InputError :message="formSettings.errors.market" class="mt-2" />
                                </div>
                                <SaveButton
                                    :disabled="formSettings.processing"
                                    :saved="formSettings.recentlySuccessful"
                                ></SaveButton>
                            </form>
                        </div>
                    </div>
<!--                    <ExchangeRateMarkup
                        v-if="viewStore.isAdminViewMode"
                    />-->
                </div>
            </div>
        </div>
        <div class="space-y-3">
            <div class="lg:flex block justify-between items-center">
                <h3 class="text-xl font-medium text-gray-900 dark:text-white">Методы</h3>
                <div class="flex items-center">
<!--                        <span class="flex text-xs text-gray-900 dark:text-gray-200 mr-3">
                            <svg class="w-4 h-4 text-gray-500 dark:text-gray-200 mr-1" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 12c.263 0 .524-.06.767-.175a2 2 0 0 0 .65-.491c.186-.21.333-.46.433-.734.1-.274.15-.568.15-.864a2.4 2.4 0 0 0 .586 1.591c.375.422.884.659 1.414.659.53 0 1.04-.237 1.414-.659A2.4 2.4 0 0 0 12 9.736a2.4 2.4 0 0 0 .586 1.591c.375.422.884.659 1.414.659.53 0 1.04-.237 1.414-.659A2.4 2.4 0 0 0 16 9.736c0 .295.052.588.152.861s.248.521.434.73a2 2 0 0 0 .649.488 1.809 1.809 0 0 0 1.53 0 2.03 2.03 0 0 0 .65-.488c.185-.209.332-.457.433-.73.1-.273.152-.566.152-.861 0-.974-1.108-3.85-1.618-5.121A.983.983 0 0 0 17.466 4H6.456a.986.986 0 0 0-.93.645C5.045 5.962 4 8.905 4 9.736c.023.59.241 1.148.611 1.567.37.418.865.667 1.389.697Zm0 0c.328 0 .651-.091.94-.266A2.1 2.1 0 0 0 7.66 11h.681a2.1 2.1 0 0 0 .718.734c.29.175.613.266.942.266.328 0 .651-.091.94-.266.29-.174.537-.427.719-.734h.681a2.1 2.1 0 0 0 .719.734c.289.175.612.266.94.266.329 0 .652-.091.942-.266.29-.174.536-.427.718-.734h.681c.183.307.43.56.719.734.29.174.613.266.941.266a1.819 1.819 0 0 0 1.06-.351M6 12a1.766 1.766 0 0 1-1.163-.476M5 12v7a1 1 0 0 0 1 1h2v-5h3v5h7a1 1 0 0 0 1-1v-7m-5 3v2h2v-2h-2Z"/>
                            </svg>
                            Комиссия мерчанта
                        </span>
                    <span class="flex text-xs text-gray-900 dark:text-gray-200 mr-3">
                            <svg class="w-4 h-4 text-gray-500 dark:text-gray-200 mr-1" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18Zm0 0a8.949 8.949 0 0 0 4.951-1.488A3.987 3.987 0 0 0 13 16h-2a3.987 3.987 0 0 0-3.951 3.512A8.948 8.948 0 0 0 12 21Zm3-11a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/>
                            </svg>
                            Комиссия клиента
                        </span>-->
                    <button v-if="gatewayEditMode === false" @click.prevent="gatewayEditMode = true" type="button" class="px-2 py-1 text-xs shadow font-medium text-blue-700 hover:text-white border border-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 rounded-xl text-center dark:border-blue-500 dark:text-blue-500 dark:hover:text-white dark:hover:bg-blue-500 dark:focus:ring-blue-800">
                        Изменить
                    </button>
                    <button v-else @click.prevent="submitGatewaySettings(); gatewayEditMode = false" type="button" class="px-2 py-1 text-xs shadow font-medium text-green-700 hover:text-white border border-green-700 hover:bg-green-800 focus:ring-4 focus:outline-none focus:ring-green-300 rounded-xl text-center dark:border-green-500 dark:text-green-500 dark:hover:text-white dark:hover:bg-green-600 dark:focus:ring-green-800">
                        Сохранить
                    </button>
                </div>
            </div>
            <div
                v-if="gatewayEditMode === true && viewStore.isAdminViewMode"
                class="p-5 sm:p-8 w-full bg-white dark:bg-gray-800 shadow rounded-plate"
            >
                <div>
                    <header>
                        <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">Макросы для настроек</h2>
                    </header>
                    <form class="mt-6 space-y-6">
                        <div class="grid lg:grid-cols-2 grid-cols-1 gap-6">
                            <div>
                                <InputLabel
                                    for="commission_macros"
                                    value="Комиссия"
                                />

                                <TextInput
                                    id="commission_macros"
                                    v-model="macros.commission"
                                    class="mt-1 block w-full"
                                    step="1"
                                    @input="applyMacros('commission')"
                                />

                                <InputHelper model-value="Установит у всех методов указанную комиссию."></InputHelper>
                            </div>
                            <div>
                                <InputLabel
                                    for="reservation_time_macros"
                                    value="Время на сделку"
                                />

                                <TextInput
                                    id="reservation_time_macros"
                                    v-model="macros.reservation_time"
                                    class="mt-1 block w-full"
                                    step="1"
                                    @input="applyMacros('reservation_time')"
                                />

                                <InputHelper model-value="Установит у всех методов указанную время на сделку"></InputHelper>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            <div
                class="mb-5"
                v-for="(gateways, currency) in groupedGateways"
            >
                <div>
                        <span class="bg-white text-xs shadow-md font-semibold py-1.5 px-3.5 dark:text-gray-200 rounded-xl dark:bg-gray-800">
                            {{ currency.toUpperCase() }}
                        </span>
                </div>
                <div class="mt-3 gap-3 grid 2xl:grid-cols-4 xl:grid-cols-2">
                    <div
                        class="rounded-plate bg-gray-200 dark:bg-gray-700 shadow-md"
                        v-for="gateway in gateways"
                    >
                        <div class="rounded-plate bg-white shadow text-sm font-semibold py-2 px-3 dark:bg-gray-800">
                            <div class="flex justify-between items-center">
                                <div :class="gatewayEditMode ? 'w-2/5' : 'w-3/5'">
                                    <div class="truncate" :class="gatewaySettings[gateway.id]['active'] ? 'text-gray-900 dark:text-gray-200' : 'text-red-700 dark:text-red-400'">
                                        {{ gateway.original_name }}
                                    </div>
<!--                                    <div class="text-xs flex" :class="gatewaySettings[gateway.id]['active'] ? 'text-gray-500 dark:text-gray-400' : 'text-red-500 dark:text-red-400'">
                                        <div class="flex items-center mr-2">
                                            <svg class="w-3 h-3 mr-0.5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 12c.263 0 .524-.06.767-.175a2 2 0 0 0 .65-.491c.186-.21.333-.46.433-.734.1-.274.15-.568.15-.864a2.4 2.4 0 0 0 .586 1.591c.375.422.884.659 1.414.659.53 0 1.04-.237 1.414-.659A2.4 2.4 0 0 0 12 9.736a2.4 2.4 0 0 0 .586 1.591c.375.422.884.659 1.414.659.53 0 1.04-.237 1.414-.659A2.4 2.4 0 0 0 16 9.736c0 .295.052.588.152.861s.248.521.434.73a2 2 0 0 0 .649.488 1.809 1.809 0 0 0 1.53 0 2.03 2.03 0 0 0 .65-.488c.185-.209.332-.457.433-.73.1-.273.152-.566.152-.861 0-.974-1.108-3.85-1.618-5.121A.983.983 0 0 0 17.466 4H6.456a.986.986 0 0 0-.93.645C5.045 5.962 4 8.905 4 9.736c.023.59.241 1.148.611 1.567.37.418.865.667 1.389.697Zm0 0c.328 0 .651-.091.94-.266A2.1 2.1 0 0 0 7.66 11h.681a2.1 2.1 0 0 0 .718.734c.29.175.613.266.942.266.328 0 .651-.091.94-.266.29-.174.537-.427.719-.734h.681a2.1 2.1 0 0 0 .719.734c.289.175.612.266.94.266.329 0 .652-.091.942-.266.29-.174.536-.427.718-.734h.681c.183.307.43.56.719.734.29.174.613.266.941.266a1.819 1.819 0 0 0 1.06-.351M6 12a1.766 1.766 0 0 1-1.163-.476M5 12v7a1 1 0 0 0 1 1h2v-5h3v5h7a1 1 0 0 0 1-1v-7m-5 3v2h2v-2h-2Z"/>
                                            </svg>
                                            {{parseFloat(gatewaySettings[gateway.id]['merchant_commission']).toFixed(1)}}%
                                        </div>
                                        <div class="flex items-center">
                                            <svg class="w-3 h-3 mr-0.5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18Zm0 0a8.949 8.949 0 0 0 4.951-1.488A3.987 3.987 0 0 0 13 16h-2a3.987 3.987 0 0 0-3.951 3.512A8.948 8.948 0 0 0 12 21Zm3-11a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/>
                                            </svg>
                                            <template v-if="gatewaySettings[gateway.id]['custom_gateway_commission'] > 0 || gatewaySettings[gateway.id]['custom_gateway_commission'] === 0">
                                                {{parseFloat(gatewaySettings[gateway.id]['custom_gateway_commission'] - gatewaySettings[gateway.id]['merchant_commission']).toFixed(1)}}%
                                            </template>
                                            <template v-else>
                                                {{parseFloat(gateway.order_service_commission_rate - gatewaySettings[gateway.id]['merchant_commission']).toFixed(1)}}%
                                            </template>
                                        </div>
                                    </div>-->
                                </div>
                                <div
                                    class="text-gray-900 dark:text-gray-200 text-xl flex justify-between items-end gap-2"
                                    :class="gatewaySettings[gateway.id]['active'] ? 'text-gray-900 dark:text-gray-200' : 'text-red-700 dark:text-red-400'"
                                >
                                    <div v-if="viewStore.isAdminViewMode" class="flex items-center gap-2">
                                        <template v-if="gatewaySettings[gateway.id]['custom_gateway_commission'] > 0 || gatewaySettings[gateway.id]['custom_gateway_commission'] === 0">
                                            <div class="text-sm text-green-500 line-through">{{gateway.order_service_commission_rate}}%</div>
                                            <div>{{ gatewaySettings[gateway.id]['custom_gateway_commission'] }}%</div>
                                        </template>
                                        <template v-else>
                                            <div>{{gateway.order_service_commission_rate}}%</div>
                                        </template>
                                    </div>
                                    <div v-else class="flex items-center gap-2">
                                        <div v-if="gatewaySettings[gateway.id]['custom_gateway_commission'] > 0 || gatewaySettings[gateway.id]['custom_gateway_commission'] === 0">{{gatewaySettings[gateway.id]['custom_gateway_commission']}}%</div>
                                        <div v-else>{{gateway.order_service_commission_rate}}%</div>
                                    </div>
                                    <input
                                        v-if="gatewayEditMode === true && viewStore.isAdminViewMode"
                                        v-model="gatewaySettings[gateway.id]['custom_gateway_commission']"
                                        type="number"
                                        step="0.1"
                                        min="0"
                                        max="100"
                                        class="w-16 p-0 m-0 bg-transparent text-center dark:text-gray-200 text-xl focus:ring-0 border-0 border-b border-gray-400"
                                        @input="setCustomGatewayCommission(gatewaySettings[gateway.id], gateway.order_service_commission_rate, $event.target.value)"
                                    />
                                </div>
                                <!--                                <div class="text-gray-900 dark:text-gray-200 text-xl flex justify-between items-end gap-2">
                                                                        <div v-if="viewStore.isAdminViewMode" class="flex items-center gap-2">
                                                                            <template v-if="gatewaySettings[gateway.id]['custom_gateway_commission'] > 0 || gatewaySettings[gateway.id]['custom_gateway_commission'] === 0">
                                                                                <div class="text-sm text-green-500 line-through">{{gateway.order_service_commission_rate}}%</div>
                                                                                <div>{{ gatewaySettings[gateway.id]['custom_gateway_commission'] }}%</div>
                                                                            </template>
                                                                            <template v-else>
                                                                                <div>{{gateway.order_service_commission_rate}}%</div>
                                                                            </template>
                                                                        </div>
                                                                        <div v-else class="flex items-center gap-2">
                                                                            <div v-if="gatewaySettings[gateway.id]['custom_gateway_commission'] > 0 || gatewaySettings[gateway.id]['custom_gateway_commission'] === 0">{{gatewaySettings[gateway.id]['custom_gateway_commission']}}%</div>
                                                                            <div v-else>{{gateway.order_service_commission_rate}}%</div>
                                                                        </div>
                                                                        <input
                                                                            v-if="commissionEditMode === true && viewStore.isAdminViewMode"
                                                                            v-model="gatewaySettings[gateway.id]['custom_gateway_commission']"
                                                                            type="number"
                                                                            step="0.1"
                                                                            min="0"
                                                                            max="100"
                                                                            class="w-16 p-0 m-0 bg-transparent text-center dark:text-gray-200 text-xl focus:ring-0 border-0 border-b border-gray-400"
                                                                            @input="setCustomCommission(gatewaySettings[gateway.id], $event.target.value)"
                                                                        />
                                                                    </div>-->
                            </div>
<!--                            <div v-if="gatewayEditMode === true" class="flex items-center mt-2">
                                <svg class="w-4 h-4 text-gray-500 dark:text-gray-400 mr-1" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 12c.263 0 .524-.06.767-.175a2 2 0 0 0 .65-.491c.186-.21.333-.46.433-.734.1-.274.15-.568.15-.864a2.4 2.4 0 0 0 .586 1.591c.375.422.884.659 1.414.659.53 0 1.04-.237 1.414-.659A2.4 2.4 0 0 0 12 9.736a2.4 2.4 0 0 0 .586 1.591c.375.422.884.659 1.414.659.53 0 1.04-.237 1.414-.659A2.4 2.4 0 0 0 16 9.736c0 .295.052.588.152.861s.248.521.434.73a2 2 0 0 0 .649.488 1.809 1.809 0 0 0 1.53 0 2.03 2.03 0 0 0 .65-.488c.185-.209.332-.457.433-.73.1-.273.152-.566.152-.861 0-.974-1.108-3.85-1.618-5.121A.983.983 0 0 0 17.466 4H6.456a.986.986 0 0 0-.93.645C5.045 5.962 4 8.905 4 9.736c.023.59.241 1.148.611 1.567.37.418.865.667 1.389.697Zm0 0c.328 0 .651-.091.94-.266A2.1 2.1 0 0 0 7.66 11h.681a2.1 2.1 0 0 0 .718.734c.29.175.613.266.942.266.328 0 .651-.091.94-.266.29-.174.537-.427.719-.734h.681a2.1 2.1 0 0 0 .719.734c.289.175.612.266.94.266.329 0 .652-.091.942-.266.29-.174.536-.427.718-.734h.681c.183.307.43.56.719.734.29.174.613.266.941.266a1.819 1.819 0 0 0 1.06-.351M6 12a1.766 1.766 0 0 1-1.163-.476M5 12v7a1 1 0 0 0 1 1h2v-5h3v5h7a1 1 0 0 0 1-1v-7m-5 3v2h2v-2h-2Z"/>
                                </svg>

                                <input v-if="gatewaySettings[gateway.id]['custom_gateway_commission'] > 0 || gatewaySettings[gateway.id]['custom_gateway_commission'] === 0" style="rotate: 180deg" type="range" v-model="gatewaySettings[gateway.id]['merchant_commission']" min="0" :max="gatewaySettings[gateway.id]['custom_gateway_commission']" step="0.1" class="w-full h-1 bg-gray-200 rounded-lg appearance-none cursor-pointer range-sm dark:bg-gray-700">
                                <input v-else style="rotate: 180deg" type="range" v-model="gatewaySettings[gateway.id]['merchant_commission']" min="0" :max="gateway.order_service_commission_rate" step="0.1" class="w-full h-1 bg-gray-200 rounded-lg appearance-none cursor-pointer range-sm dark:bg-gray-700">

                                <svg class="w-4 h-4 text-gray-500 dark:text-gray-400 ml-1" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18Zm0 0a8.949 8.949 0 0 0 4.951-1.488A3.987 3.987 0 0 0 13 16h-2a3.987 3.987 0 0 0-3.951 3.512A8.948 8.948 0 0 0 12 21Zm3-11a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/>
                                </svg>
                            </div>-->
                        </div>
                        <div v-if="gatewayEditMode === true" class="py-2 px-4 flex justify-between items-center">
                            <span class="text-xs text-gray-700 dark:text-gray-400">Включен</span>
                            <label class="inline-flex items-center cursor-pointer">
                                <input type="checkbox" value="" class="sr-only peer" v-model="gatewaySettings[gateway.id]['active']">
                                <div class="relative w-7 h-4 bg-gray-400 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 rounded-full peer peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-3 after:w-3 after:transition-all dark:border-gray-600 peer-checked:bg-blue-600"></div>
                            </label>
                        </div>
                        <div v-if="viewStore.isAdminViewMode && gatewayEditMode === true" class="py-2 px-4 flex justify-between items-center">
                            <span class="text-xs text-gray-700 dark:text-gray-400">Время на сделку</span>
                            <input
                                v-model="gatewaySettings[gateway.id]['custom_gateway_reservation_time']"
                                type="text"
                                class="w-16 p-0 m-0 bg-transparent text-center dark:text-gray-200 text-base focus:ring-0 border-0 border-b border-gray-400"
                                @input="setCustomReservationTime(gatewaySettings[gateway.id], $event.target.value)"
                            />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>

</style>
