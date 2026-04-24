<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import {Head, router, useForm, usePage} from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import {computed, ref, watch, onMounted} from "vue";
import Select from "@/Components/Select.vue";
import NumberInput from "@/Components/NumberInput.vue";
import SaveButton from "@/Components/Form/SaveButton.vue";
import SecondaryPageSection from "@/Wrappers/SecondaryPageSection.vue";
import {useViewStore} from "@/store/view.js";
import NumberInputBlock from "@/Components/Form/NumberInputBlock.vue";
import Multiselect from "@/Components/Form/Multiselect.vue";
import InputBlock from "@/Components/Form/InputBlock.vue";
import InputNumber from 'primevue/inputnumber';
import Tooltip from 'primevue/tooltip';
import Button from 'primevue/button'; 

const viewStore = useViewStore();
const payment_gateways = usePage().props.paymentGateways;
const detail_type_names = {
    'card': 'Карта',
    'phone': 'Телефон',
    'account_number': 'Номер счета',
    'sim': 'Сим-карта',
}

const currentUser = usePage().props.auth?.user;

// Определяем, является ли текущий пользователь VIP
const isVipUser = computed(() => {
    return currentUser?.is_vip === true || currentUser?.is_vip === 1;
});

// Получаем уникальные валюты из платежных методов
const availableCurrencies = computed(() => {
    const currencies = [...new Set(payment_gateways.map(pg => pg.currency))];
    return currencies.map(currency => ({
        id: currency,
        name: currency.toUpperCase()
    }));
});

const selectedDetailType = ref(null);

const form = useForm({
    name: '',
    detail: '',
    initials: '',
    is_active: true,
    daily_limit: '',
    max_pending_orders_quantity: 1,
    payment_gateway_ids: [],
    detail_type: null,
    user_device_id: 0,
    order_interval_minutes: '',
    currency: null,
    unique_amount_percentage: 3.0,
    unique_amount_seconds: 600,
});

const details = ref({
    'card': '',
    'phone': '',
    'account_number': '',
    'sim': '',
});

// Доступные типы реквизитов для выбранной валюты
const availableDetailTypes = computed(() => {
    if (!form.currency) return [];

    // Получаем уникальные типы реквизитов из платежных методов с выбранной валютой
    const types = new Set();
    payment_gateways
        .filter(pg => pg.currency.toLowerCase() === form.currency.toLowerCase())
        .forEach(pg => {
            pg.detail_types.forEach(type => types.add(type));
        });

    return Array.from(types).map(type => ({
        id: type,
        name: detail_type_names[type]
    }));
});

// Доступные платежные методы с учетом валюты и типа реквизита
const formattedPaymentGateways = computed(() => {
    if (!form.currency || !selectedDetailType.value) return [];

    const gateways = payment_gateways
        .filter(pg =>
            pg.currency.toLowerCase() === form.currency.toLowerCase() &&
            pg.detail_types.includes(selectedDetailType.value)
        )
        .map(pg => ({
            value: pg.id,
            label: pg.name
        }));

    return gateways;
});

// Следим за изменением типа реквизита
watch(selectedDetailType, (newType) => {
    // Сбрасываем выбранные платежные методы при смене типа реквизита
    form.payment_gateway_ids = [];
    form.detail_type = newType;

    // Очищаем значение детали для предыдущего типа
    if (newType) {
        Object.keys(details.value).forEach(key => {
            if (key !== newType) {
                details.value[key] = '';
            }
        });
    }
});

// Определяем, можно ли выбрать несколько платежных методов
const isMultipleGatewaysAllowed = computed(() => {
    //return selectedDetailType.value === 'phone';
    return false;
});

const submit = () => {
    form
        .transform((data) => {
            if (data.user_device_id === 0) {
                data.user_device_id = null;
            }
            data.detail_type = selectedDetailType.value;
            data.detail = details.value[data.detail_type];

            return data;
        })
        .post(route('payment-details.store'), {
            preserveScroll: true,
            onSuccess: () => {
                router.visit(route(viewStore.adminPrefix + 'payment-details.index'))
            },
        });
};

const devices = usePage().props.devices;

const formattedDevices = computed(() => {
    return devices.map(device => ({
        ...device,
        name: `${device.name}`
    }));
});

defineOptions({ layout: AuthenticatedLayout })
</script>

<template>
    <div>
        <Head title="Создание нового реквизита" />

        <SecondaryPageSection
            :back-link="route(viewStore.adminPrefix + 'payment-details.index')"
            title="Создание нового реквизита"
            description="Здесь вы можете создать новые платежные реквизиты."
        >
            <form @submit.prevent="submit" class="mt-6 space-y-6">

                <div class="md:grid md:grid-cols-2 md:gap-x-8 md:gap-y-6">
                <div>
                    <div class="flex items-center mb-1">
                        <InputLabel
                            for="currency"
                            value="Валюта"
                            :error="!!form.errors.currency"
                        />
                        <i class="pi pi-info-circle ml-2 cursor-pointer text-gray-500 dark:text-gray-400"
                           v-tooltip.right="'Выберите валюту для реквизитов. Это определит доступные типы реквизитов и платежные шлюзы.'"></i>
                    </div>
                    <Select
                        id="currency"
                        v-model="form.currency"
                        :error="!!form.errors.currency"
                        :items="availableCurrencies"
                        value="id"
                        name="name"
                        default_title="Выберите валюту"
                        :default_value="null"
                        @change="selectedDetailType = null; form.payment_gateway_ids = []; form.clearErrors('currency')"
                    ></Select>
                    <InputError :message="form.errors.currency" class="mt-2" />
                </div>


                <div v-if="form.currency">
                    <div class="flex items-center mb-1">
                        <InputLabel
                            for="detail_type"
                            value="Тип реквизита"
                            :error="!!form.errors.detail_type"
                        />
                        <i class="pi pi-info-circle ml-2 cursor-pointer text-gray-500 dark:text-gray-400"
                           v-tooltip.right="'Выберите тип создаваемых реквизитов (например, Карта, Телефон).'"></i>
                    </div>
                    <Select
                        id="detail_type"
                        v-model="selectedDetailType"
                        :error="!!form.errors.detail_type"
                        :items="availableDetailTypes"
                        value="id"
                        name="name"
                        default_title="Выберите тип реквизита"
                        :default_value="null"
                        @change="form.clearErrors('detail_type')"
                    ></Select>
                    <InputError :message="form.errors.detail_type" class="mt-2" />
                </div>
    </div>

                <div v-if="selectedDetailType">
                     <div class="flex items-center mb-1">
                        <InputLabel
                            for="payment_gateway_ids"
                            :value="isMultipleGatewaysAllowed ? 'Платежные методы' : 'Платежный метод'"
                            :error="!!form.errors.payment_gateway_ids"
                        />
                        <i class="pi pi-info-circle ml-2 cursor-pointer text-gray-500 dark:text-gray-400"
                           v-tooltip.right="'Выберите платежный шлюз, который будет работать с этими реквизитами.'"></i>
                    </div>
                    <Multiselect
                        id="payment_gateway_ids"
                        v-model="form.payment_gateway_ids"
                        :options="formattedPaymentGateways"
                        :error="!!form.errors.payment_gateway_ids"
                        @change="form.clearErrors('payment_gateway_ids')"
                        :enable-search="true"
                        :single-select="!isMultipleGatewaysAllowed"
                        :placeholder="isMultipleGatewaysAllowed ? 'Выберите платежные методы' : 'Выберите платежный метод'"
                    />
                    <InputError :message="form.errors.payment_gateway_ids" class="mt-2"/>
                </div>

                <template v-if="selectedDetailType">
                    <div class="md:grid md:grid-cols-2 md:gap-x-8 md:gap-y-6">
                    <div class="mt-4 md:mt-0">
                        <div class="flex items-center mb-1">
                            <InputLabel
                                for="user_device_id"
                                value="Устройство"
                                :error="!!form.errors.user_device_id"
                            />
                            <i class="pi pi-info-circle ml-2 cursor-pointer text-gray-500 dark:text-gray-400"
                               v-tooltip.right="'Выберите устройство, к которому будут привязаны эти реквизиты, если это необходимо.'"></i>
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
                    <div class="mt-4 md:mt-0">
                        <div class="flex items-center mb-1">
                            <InputLabel
                                for="name"
                                value="Никнейм реквизитов"
                                :error="!!form.errors.name"
                            />
                            <i class="pi pi-info-circle ml-2 cursor-pointer text-gray-500 dark:text-gray-400"
                               v-tooltip.right="'Придумайте удобное имя для этих реквизитов, чтобы легко их идентифицировать.'"></i>
                        </div>
                        <TextInput
                            id="name"
                            v-model="form.name"
                            type="text"
                            class="mt-1 block w-full"
                            :error="!!form.errors.name"
                            @input="form.clearErrors('name')"
                        />
                        <InputError :message="form.errors.name" class="mt-2" />
                    </div>
</div>
                    <div class="md:grid md:grid-cols-2 md:gap-x-8 md:gap-y-6">
                    <!-- Поле для ввода карты -->
                    <div v-if="selectedDetailType === 'card'" class="mt-4 md:mt-0">
                        <div class="flex items-center mb-1">
                            <InputLabel
                                for="detail_card"
                                value="Карта"
                                :error="!!form.errors.detail"
                            />
                            <i class="pi pi-info-circle ml-2 cursor-pointer text-gray-500 dark:text-gray-400"
                               v-tooltip.right="'Введите номер банковской карты.'"></i>
                        </div>
                        <TextInput
                            id="detail_card"
                            v-model="details['card']"
                            type="text"
                            class="mt-1 block w-full"
                            placeholder="0000 0000 0000 0000"
                            :error="!!form.errors.detail"
                            @input="form.clearErrors('detail')"
                        />
                        <InputError :message="form.errors.detail" class="mt-2" />
                    </div>

                    <!-- Поле для ввода телефона -->
                    <div v-if="selectedDetailType === 'phone'" class="mt-4 md:mt-0">
                        <div class="flex items-center mb-1">
                            <InputLabel
                                for="detail_phone"
                                value="Номер телефона"
                                :error="!!form.errors.detail"
                            />
                            <i class="pi pi-info-circle ml-2 cursor-pointer text-gray-500 dark:text-gray-400"
                               v-tooltip.right="'Введите номер телефона, который будет использоваться для платежей (без +).'"></i>
                        </div>
                        <div class="relative">
                            <div class="absolute inset-y-0 start-0 flex items-center ps-3.5 pointer-events-none">
                                <span class="text-gray-500 dark:text-gray-400">+</span>
                            </div>
                            <TextInput
                                id="detail_phone"
                                v-model="details['phone']"
                                type="text"
                                class="mt-1 block w-full ps-7"
                                :error="!!form.errors.detail"
                                @input="form.clearErrors('detail')"
                            />
                        </div>
                        <InputError :message="form.errors.detail" class="mt-2" />
                    </div>

                    <!-- Поле для ввода номера счета -->
                    <div v-if="selectedDetailType === 'account_number'" class="mt-4 md:mt-0">
                         <div class="flex items-center mb-1">
                            <InputLabel
                                for="detail_account"
                                value="Номер счета"
                                :error="!!form.errors.detail"
                            />
                            <i class="pi pi-info-circle ml-2 cursor-pointer text-gray-500 dark:text-gray-400"
                               v-tooltip.right="'Введите номер банковского счета.'"></i>
                        </div>
                        <TextInput
                            id="detail_account"
                            v-model="details['account_number']"
                            type="text"
                            class="mt-1 block w-full"
                            placeholder="00000000000000000000"
                            :error="!!form.errors.detail"
                            @input="form.clearErrors('detail')"
                        />
                        <InputError :message="form.errors.detail" class="mt-2" />
                    </div>

                    <!-- Поле для ввода сим-карты (номер телефона) -->
                    <div v-if="selectedDetailType === 'sim'" class="mt-4 md:mt-0">
                        <div class="flex items-center mb-1">
                            <InputLabel
                                for="detail_sim"
                                value="Номер телефона сим-карты"
                                :error="!!form.errors.detail"
                            />
                            <i class="pi pi-info-circle ml-2 cursor-pointer text-gray-500 dark:text-gray-400"
                               v-tooltip.right="'Введите номер телефона сим-карты (без +).'"></i>
                        </div>
                        <div class="relative">
                            <div class="absolute inset-y-0 start-0 flex items-center ps-3.5 pointer-events-none">
                                <span class="text-gray-500 dark:text-gray-400">+</span>
                            </div>
                            <TextInput
                                id="detail_sim"
                                v-model="details['sim']"
                                type="text"
                                class="mt-1 block w-full ps-7"
                                :error="!!form.errors.detail"
                                @input="form.clearErrors('detail')"
                            />
                        </div>
                        <InputError :message="form.errors.detail" class="mt-2" />
                    </div>

                    <div class="mt-4 md:mt-0">
                        <div class="flex items-center mb-1">
                            <InputLabel
                                for="initials"
                                value="Инициалы (имя получателя)"
                                :error="!!form.errors.initials"
                            />
                            <i class="pi pi-info-circle ml-2 cursor-pointer text-gray-500 dark:text-gray-400"
                               v-tooltip.right="'Введите ФИО или инициалы получателя платежа.'"></i>
                        </div>
                        <TextInput
                            id="initials"
                            v-model="form.initials"
                            type="text"
                            class="mt-1 block w-full"
                            :error="!!form.errors.initials"
                            @input="form.clearErrors('initials')"
                        />
                        <InputError :message="form.errors.initials" class="mt-2" />
                    </div>
                      </div>

                      <div class="md:grid md:grid-cols-2 md:gap-x-8 md:gap-y-6">
                    <div class="mt-4 md:mt-0">
                        <div class="flex items-center mb-1">
                            <InputLabel
                                for="daily_limit"
                                :value="'Лимит на объем операций в сутки (' + form.currency?.toUpperCase() + ')'"
                                :error="!!form.errors.daily_limit"
                            />
                            <i class="pi pi-info-circle ml-2 cursor-pointer text-gray-500 dark:text-gray-400"
                               v-tooltip.right="'Максимальная сумма операций в выбранной валюте за 24 часа. Оставьте пустым, если лимит не требуется.'"></i>
                        </div>
                        <NumberInput
                            id="daily_limit"
                            v-model="form.daily_limit"
                            class="mt-1 block w-full"
                            :error="!!form.errors.daily_limit"
                            @input="form.clearErrors('daily_limit')"
                        />
                        <InputError :message="form.errors.daily_limit" class="mt-2" />
                    </div>

                    <div v-if="viewStore.isAdminViewMode" class="mt-4 md:mt-0">
                        <div class="flex items-center mb-1">
                            <InputLabel
                                for="max_pending_orders_quantity"
                                value="Максимальное количество активных сделок"
                                :error="!!form.errors.max_pending_orders_quantity"
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
                        <InputError :message="form.errors.max_pending_orders_quantity" class="mt-2" />
                    </div>

                    <div class="mt-4 md:mt-0">
                        <div class="flex items-center mb-1">
                            <InputLabel
                                for="order_interval_minutes"
                                value="Интервал между сделками (минуты)"
                                :error="!!form.errors.order_interval_minutes"
                            />
                            <i class="pi pi-info-circle ml-2 cursor-pointer text-gray-500 dark:text-gray-400"
                               v-tooltip.right="'Минимальное время в минутах между успешными операциями. Оставьте пустым для отключения интервала.'"></i>
                        </div>
                        <NumberInputBlock
                            v-model="form.order_interval_minutes"
                            :form="form"
                            field="order_interval_minutes"
                            :helper="form.order_interval_minutes ? null : 'Оставьте пустым для отключения интервала'"
                            :show-label="false"
                        />
                         <InputError :message="form.errors.order_interval_minutes" class="mt-2" />
                    </div>

                    <div class="mt-4 md:mt-0">
                        <div class="flex items-center mb-1">
                            <InputLabel value="Статус" :error="!!form.errors.is_active" />
                            <i class="pi pi-info-circle ml-2 cursor-pointer text-gray-500 dark:text-gray-400"
                               v-tooltip.right="'Определяет, будут ли реквизиты активны сразу после создания.'"></i>
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
                </div>
                </template>

                <!-- Секция Суперзалива -->
                

                <div class="mt-8">
                    <SaveButton
                        :disabled="form.processing"
                        :saved="form.recentlySuccessful"
                    ></SaveButton>
                </div>
            </form>
        </SecondaryPageSection>
    </div>
</template>
