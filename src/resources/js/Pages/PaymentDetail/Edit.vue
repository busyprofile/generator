<script setup>
import {Head, useForm, usePage} from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import {computed} from "vue";
import Select from "@/Components/Select.vue";
import SaveButton from "@/Components/Form/SaveButton.vue";
import SecondaryPageSection from "@/Wrappers/SecondaryPageSection.vue";
import TextInputBlock from "@/Components/Form/TextInputBlock.vue";
import NumberInputBlock from "@/Components/Form/NumberInputBlock.vue";
import {useViewStore} from "@/store/view.js";
import InputLabel from "@/Components/InputLabel.vue";
import InputError from "@/Components/InputError.vue";
import InputNumber from 'primevue/inputnumber';
import Tooltip from 'primevue/tooltip';
import Button from 'primevue/button';

const viewStore = useViewStore();
const currentUser = usePage().props.auth?.user;

const payment_detail = usePage().props.paymentDetail;
const payment_gateways = usePage().props.paymentGateways;
const devices = usePage().props.devices;

const isVipUser = computed(() => {
    return currentUser?.is_vip === true || currentUser?.is_vip === 1;
});

const formattedPaymentGateways = computed(() => {
    if (!payment_gateways || !payment_detail) return [];
    return payment_gateways
        .filter(pg =>
            pg.currency.toLowerCase() === payment_detail.currency?.toLowerCase() &&
            pg.detail_types.includes(payment_detail.detail_type)
        )
        .map(pg => ({
            value: pg.id,
            label: pg.name
        }));
});

const form = useForm({
    name: payment_detail.name,
    initials: payment_detail.initials,
    is_active: !!payment_detail.is_active,
    daily_limit: payment_detail.daily_limit,
    max_pending_orders_quantity: payment_detail.max_pending_orders_quantity,
    min_order_amount: payment_detail.min_order_amount,
    max_order_amount: payment_detail.max_order_amount,
    order_interval_minutes: payment_detail.order_interval_minutes,
    user_device_id: payment_detail.user_device_id ?? 0,
    payment_gateway_ids: payment_detail.payment_gateway_ids ?? [],
    unique_amount_percentage: payment_detail.unique_amount_percentage ?? 3.0,
    unique_amount_seconds: payment_detail.unique_amount_seconds ?? 600,
    detail_type: payment_detail.detail_type,
    detail: payment_detail.detail,
});

const formattedDevices = computed(() => {
    if (!devices) return [];
    return devices.map(device => ({
        ...device,
        name: `${device.name}`
    }));
});

const submit = () => {
    const dataToSubmit = {
        ...form.data(),
        payment_gateway_ids: form.payment_gateway_ids && form.payment_gateway_ids.length > 0 ? [form.payment_gateway_ids[0]] : [],
    };

    if (dataToSubmit.user_device_id === 0) {
        dataToSubmit.user_device_id = null;
    }

    form.transform(() => dataToSubmit)
        .patch(route('payment-details.update', payment_detail.id), {
            preserveScroll: true,
            onSuccess: () => {
            },
            onError: (errors) => {
                console.error('Ошибки сохранения:', errors);
            }
        });
};

defineOptions({ layout: AuthenticatedLayout })
</script>

<template>
    <div>
        <Head :title="'Редактирование реквизита - ' + form.name" />

        <SecondaryPageSection
            :back-link="route(viewStore.adminPrefix + 'payment-details.index')"
            :title="'Редактирование реквизита - ' + form.name"
            description="Здесь вы можете редактировать платежные реквизиты."
        >
            <form @submit.prevent="submit" class="mt-6 space-y-6">

                  <div class="md:grid md:grid-cols-2 md:gap-x-8 md:gap-y-6"> 
                <div class="mt-4">
                    <div class="flex items-center">
                        <InputLabel
                            for="user_device_id"
                            value="Устройство"
                            :error="!!form.errors.user_device_id"
                            class="mb-1"
                        />
                        <i class="pi pi-info-circle ml-2 cursor-pointer text-gray-500 dark:text-gray-400"
                           v-tooltip.right="'Выберите устройство, к которому привязаны эти реквизиты.'"></i>
                    </div>
                    <Select
                        id="user_device_id"
                        v-model="form.user_device_id"
                        :error="!!form.errors.user_device_id"
                        :items="formattedDevices"
                        value="id"
                        name="name"
                        default_title="Выберите устройство"
                        @change="form.clearErrors('user_device_id')"
                    ></Select>
                    <InputError :message="form.errors.user_device_id" class="mt-2"/>
                </div>

                <div class="mt-4">
                    <div class="flex items-center">
                        <InputLabel
                            for="payment_gateway_ids"
                            value="Платежный метод"
                            :error="!!form.errors.payment_gateway_ids"
                            class="mb-1"
                        />
                        <i class="pi pi-info-circle ml-2 cursor-pointer text-gray-500 dark:text-gray-400"
                           v-tooltip.right="'Выберите платежный метод, который будет использоваться с этими реквизитами.'"></i>
                    </div>
                    <Select
                        id="payment_gateway_ids"
                        v-model="form.payment_gateway_ids[0]"
                        :error="!!form.errors.payment_gateway_ids"
                        :items="formattedPaymentGateways"
                        value="value"
                        name="label"
                        default_title="Выберите платежный метод"
                        @change="form.clearErrors('payment_gateway_ids')"
                    ></Select>
                    <InputError :message="form.errors.payment_gateway_ids" class="mt-2"/>
                </div>
                </div>


                  <div class="md:grid md:grid-cols-2 md:gap-x-8 md:gap-y-6"> 
                <div class="mt-4">
                    <div class="flex items-center">
                        <InputLabel
                            for="name"
                            value="Никнейм реквизитов"
                            :error="!!form.errors.name"
                            class="mb-1"
                        />
                        <i class="pi pi-info-circle ml-2 cursor-pointer text-gray-500 dark:text-gray-400"
                           v-tooltip.right="'Удобное имя для идентификации этих реквизитов в системе.'"></i>
                    </div>
                    <TextInputBlock
                        v-model="form.name"
                        :form="form"
                        field="name"
                        :show-label="false" 
                    />
                </div>

                <div class="mt-4">
                    <div class="flex items-center">
                        <InputLabel
                            for="initials"
                            value="Инициалы (имя получателя)"
                            :error="!!form.errors.initials"
                            class="mb-1"
                        />
                        <i class="pi pi-info-circle ml-2 cursor-pointer text-gray-500 dark:text-gray-400"
                           v-tooltip.right="'Полное имя или инициалы владельца реквизитов, как они должны отображаться для плательщика.'"></i>
                    </div>
                    <TextInputBlock
                        v-model="form.initials"
                        :form="form"
                        field="initials"
                        :show-label="false"
                    />
                </div>
            </div>


            <div class="md:grid md:grid-cols-2 md:gap-x-8 md:gap-y-6"> 
                <div class="mt-4">
                    <div class="flex items-center">
                        <InputLabel
                            for="daily_limit"
                            :value="'Лимит на объем операций в сутки (' + payment_detail.currency?.toUpperCase() + ')'"
                            :error="!!form.errors.daily_limit"
                            class="mb-1"
                        />
                        <i class="pi pi-info-circle ml-2 cursor-pointer text-gray-500 dark:text-gray-400"
                           v-tooltip.right="'Максимальная общая сумма операций, которая может пройти через эти реквизиты за 24 часа.'"></i>
                    </div>
                    <NumberInputBlock
                        v-model="form.daily_limit"
                        :form="form"
                        field="daily_limit"
                        :show-label="false"
                    />
                </div>

                <div class="mt-4" v-if="viewStore.isAdminViewMode || isVipUser">
                    <div class="flex items-center">
                        <InputLabel
                            for="max_pending_orders_quantity"
                            value="Максимальное количество активных сделок"
                            :error="!!form.errors.max_pending_orders_quantity"
                            class="mb-1"
                        />
                        <i class="pi pi-info-circle ml-2 cursor-pointer text-gray-500 dark:text-gray-400"
                           v-tooltip.right="'Максимальное число сделок, которые могут одновременно находиться в ожидании оплаты по этим реквизитам.'"></i>
                    </div>
                    <NumberInputBlock
                        v-model="form.max_pending_orders_quantity"
                        :form="form"
                        field="max_pending_orders_quantity"
                        :show-label="false"
                    />
                </div>
 </div>

<div class="md:grid md:grid-cols-2 md:gap-x-8 md:gap-y-6"> 
                <div class="mt-4">
                    <div class="flex items-center">
                        <InputLabel
                            for="order_interval_minutes"
                            value="Интервал между сделками (минуты)"
                            :error="!!form.errors.order_interval_minutes"
                            class="mb-1"
                        />
                        <i class="pi pi-info-circle ml-2 cursor-pointer text-gray-500 dark:text-gray-400"
                           v-tooltip.right="'Минимальное время в минутах, которое должно пройти между успешными операциями по этим реквизитам. Оставьте пустым для отключения интервала.'"></i>
                    </div>
                    <NumberInputBlock
                        v-model="form.order_interval_minutes"
                        :form="form"
                        field="order_interval_minutes"
                        :show-label="false"
                    />
                </div>
 <div class="md:flex justify-between col-span-2 md:col-span-1 gap-4  lg:grid lg:grid-cols-2  "> 
                <div class="mt-4">
                     <div class="flex items-center mb-1">
                        <InputLabel value="Статус" :error="!!form.errors.is_active" />
                        <i class="pi pi-info-circle ml-2 cursor-pointer text-gray-500 dark:text-gray-400"
                           v-tooltip.right="'Определяет, могут ли использоваться эти реквизиты в системе.'"></i>
                    </div>
                    <Button
                        :label="form.is_active ? 'Включен' : 'Отключен'"
                        :icon="form.is_active ? 'pi pi-check-circle' : 'pi pi-times-circle'"
                        :severity="form.is_active ? 'success' : 'danger'"
                        @click="form.is_active = !form.is_active; form.clearErrors('is_active')"
                        class="p-button-sm w-full md:w-auto"
                        outlined
                    />
                    <InputError :message="form.errors.is_active" class="mt-2"/>
                </div>

                 <div v-if="viewStore.isAdminViewMode" class="mt-4">
                    <div class="flex items-center">
                        <label for="owner_email" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Владелец</label>
                        <i class="pi pi-info-circle ml-2 cursor-pointer text-gray-500 dark:text-gray-400"
                           v-tooltip.right="'Пользователь, которому принадлежат эти реквизиты.'"></i>
                    </div>
                    <div class="dark:text-gray-300 mt-1 block w-full">
                        {{payment_detail.owner_email}}
                    </div>
                </div>
</div>
                 </div>

                 <div class="md:grid md:grid-cols-2 md:gap-x-8 md:gap-y-6"> 
                                <div class="mt-4" v-if="viewStore.isAdminViewMode || isVipUser">
                    <div class="flex items-center">
                        <InputLabel
                            for="min_order_amount"
                            :value="'Минимальная сумма сделки (' + payment_detail.currency?.toUpperCase() + ')'"
                            :error="!!form.errors.min_order_amount"
                            class="mb-1"
                        />
                         <i class="pi pi-info-circle ml-2 cursor-pointer text-gray-500 dark:text-gray-400"
                           v-tooltip.right="'Минимальная сумма для одной операции по этим реквизитам. Оставьте пустым для отключения лимита.'"></i>
                    </div>
                    <NumberInputBlock
                        v-model="form.min_order_amount"
                        :form="form"
                        field="min_order_amount"
                        :show-label="false"
                    />
                </div>

                <div class="mt-4" v-if="viewStore.isAdminViewMode || isVipUser">
                     <div class="flex items-center">
                        <InputLabel
                            for="max_order_amount"
                            :value="'Максимальная сумма сделки (' + payment_detail.currency?.toUpperCase() + ')'"
                            :error="!!form.errors.max_order_amount"
                            class="mb-1"
                        />
                        <i class="pi pi-info-circle ml-2 cursor-pointer text-gray-500 dark:text-gray-400"
                           v-tooltip.right="'Максимальная сумма для одной операции по этим реквизитам. Оставьте пустым для отключения лимита.'"></i>
                    </div>
                    <NumberInputBlock
                        v-model="form.max_order_amount"
                        :form="form"
                        field="max_order_amount"
                        :show-label="false"
                    />
                </div>
 </div>
                

                <div v-if="viewStore.isAdminViewMode || isVipUser" class="md:col-span-2">
                    <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg p-4 mb-4">
                        <h3 class="text-lg font-semibold text-green-700 dark:text-green-400 mb-2 flex items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M11.3 1.046A1 1 0 0112 2v5h4a1 1 0 01.82 1.573l-7 10A1 1 0 018 18v-5H4a1 1 0 01-.82-1.573l7-10a1 1 0 011.12-.381z" clip-rule="evenodd" />
                            </svg>
                            Суперзалив (VIP)
                        </h3>
                        <p class="text-sm text-green-600 dark:text-green-300 mb-4">
                            Настройте параметры для проверки уникальности суммы заказа, чтобы лучше управлять потоком заказов.
                        </p>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label for="unique_amount_percentage" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Процент отклонения суммы
                                    <span class="text-xs text-gray-500 dark:text-gray-400 ml-1">(0-10%)</span>
                                </label>
                                <div class="relative">
                                    <InputNumber
                                        v-model="form.unique_amount_percentage"
                                        id="unique_amount_percentage"
                                        class="w-full"
                                        :min="0"
                                        :max="10"
                                        :step="0.01"
                                        mode="decimal"
                                        :minFractionDigits="2"
                                        :maxFractionDigits="2"
                                        placeholder="Например: 3.00"
                                        suffix=" %"
                                    />
                                    <i class="pi pi-info-circle absolute right-3 top-3 text-gray-500 dark:text-gray-400"
                                       v-tooltip="'Разница между суммами заказов в процентах. Например, 3% означает, что если недавно был заказ на сумму 1000, то новые заказы в диапазоне 970-1030 будут отклонены.'"></i>
                                </div>
                                <small class="text-gray-500 dark:text-gray-400 mt-1 block">
                                    Стандартное значение: 3.0%
                                </small>
                            </div>
                            <div>
                                <label for="unique_amount_seconds" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Интервал проверки
                                    <span class="text-xs text-gray-500 dark:text-gray-400 ml-1">(0-3600 сек.)</span>
                                </label>
                                <div class="relative">
                                    <InputNumber
                                        v-model="form.unique_amount_seconds"
                                        id="unique_amount_seconds"
                                        class="w-full"
                                        :min="0"
                                        :max="3600"
                                        mode="decimal"
                                        :minFractionDigits="0"
                                        :useGrouping="false"
                                        placeholder="Например: 600"
                                        suffix=" сек."
                                    />
                                    <i class="pi pi-info-circle absolute right-3 top-3 text-gray-500 dark:text-gray-400"
                                       v-tooltip="'Период времени в секундах, за который проверяется уникальность суммы. 600 сек. = 10 минут.'"></i>
                                </div>
                                <small class="text-gray-500 dark:text-gray-400 mt-1 block">
                                    Стандартное значение: 600 секунд
                                </small>
                            </div>
                        </div>
                    </div>
                </div>

                <SaveButton
                    :disabled="form.processing"
                    :saved="form.recentlySuccessful"
                ></SaveButton>
            </form>
        </SecondaryPageSection>
    </div>
</template>