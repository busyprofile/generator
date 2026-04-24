<script setup>
import TextInput from "@/Components/TextInput.vue";
import InputError from "@/Components/InputError.vue";
import InputLabel from "@/Components/InputLabel.vue";
import SaveButton from "@/Components/Form/SaveButton.vue";
import {useForm, usePage} from "@inertiajs/vue3";
import {ref} from "vue";
import CopyUUID from "@/Components/CopyUUID.vue";
import {useViewStore} from "@/store/view.js";
import Select from "@/Components/Select.vue";
import Gateways from "@/Pages/Merchant/Tabs/Partials/Gateways.vue";
import Multiselect from "@/Components/Form/Multiselect.vue";
import DatepickerInput from "@/Pages/Merchant/Tabs/Partials/DatepickerInput.vue";

// PrimeVue Components
import Card from 'primevue/card';
import Button from 'primevue/button';
import Tag from 'primevue/tag';
import InputText from 'primevue/inputtext';
import Dropdown from 'primevue/dropdown';
import MultiSelect from 'primevue/multiselect';
import InputNumber from 'primevue/inputnumber';
import Calendar from 'primevue/calendar';

const viewStore = useViewStore();

const merchant = ref(usePage().props.merchant);
const markets = ref(usePage().props.markets);
const categories = ref(usePage().props.categories);
const currencies = ref(usePage().props.currencies || []);

// Состояние для выбора валюты
const selectedCurrency = ref('');

// Состояние для отображения минимальных сумм сделок по валютам
const minOrderAmounts = ref(merchant.value.min_order_amounts || {});

// Состояние для отображения максимальных сумм сделок по валютам
const maxOrderAmounts = ref(merchant.value.max_order_amounts || {});

const formCallback = useForm({
    callback_url: merchant.value.callback_url,
});

const formSettings = useForm({
    market: merchant.value.market,
    categories: merchant.value.categories?.map(c => c.id) || [],
    max_order_wait_time: merchant.value.max_order_wait_time,
    min_order_amounts: minOrderAmounts.value,
    max_order_amounts: maxOrderAmounts.value
});

const formStatus = useForm({});

const formResendCallback = useForm({
    start_date: '',
    end_date: '',
});

// Добавление минимальной суммы для валюты
const addMinOrderAmount = () => {
    if (!selectedCurrency.value) return;

    // Если не существует, добавляем со значением по умолчанию
    if (!minOrderAmounts.value[selectedCurrency.value]) {
        minOrderAmounts.value[selectedCurrency.value] = null; // Use null or 0 for InputNumber
    }

    // Сбрасываем выбранную валюту
    selectedCurrency.value = '';
};

// Удаление минимальной суммы для валюты
const removeMinOrderAmount = (currency) => {
    if (minOrderAmounts.value.hasOwnProperty(currency)) { // Use hasOwnProperty for safety
        const updatedAmounts = {...minOrderAmounts.value};
        delete updatedAmounts[currency];
        // Принудительное обновление реактивной переменной
        minOrderAmounts.value = updatedAmounts;
    }
};

// Добавление максимальной суммы для валюты
const addMaxOrderAmount = () => {
    if (!selectedCurrency.value) return;
    if (!maxOrderAmounts.value[selectedCurrency.value]) {
        maxOrderAmounts.value[selectedCurrency.value] = null;
    }
    selectedCurrency.value = '';
};

// Удаление максимальной суммы для валюты
const removeMaxOrderAmount = (currency) => {
    if (maxOrderAmounts.value.hasOwnProperty(currency)) {
        const updatedAmounts = {...maxOrderAmounts.value};
        delete updatedAmounts[currency];
        maxOrderAmounts.value = updatedAmounts;
    }
};

// Фильтрация доступных валют (исключаем уже добавленные в min или max)
const availableCurrencies = () => {
    return currencies.value.filter(
        currency => !minOrderAmounts.value.hasOwnProperty(currency.value) && !maxOrderAmounts.value.hasOwnProperty(currency.value)
    );
};

const submitCallback = () => {
    formCallback.patch(route('merchants.callback.update', merchant.value.id), {
        preserveScroll: true,
    });
};

const submitSettings = () => {
    formSettings
        .transform((data) => {
            data.min_order_amounts = minOrderAmounts.value;
            data.max_order_amounts = maxOrderAmounts.value;
            data.categories = formSettings.categories;
            return data;
        })
        .patch(route('admin.merchants.settings.update', merchant.value.id), {
            preserveScroll: true,
            onSuccess: (result) => {
                merchant.value = result.props.merchant;
                minOrderAmounts.value = merchant.value.min_order_amounts || {};
                maxOrderAmounts.value = merchant.value.max_order_amounts || {};
                formSettings.categories = merchant.value.categories?.map(c => c.id) || [];
            },
            onError: () => {
               // Handle potential errors, e.g., validation errors from backend
            }
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

const submitResendCallback = () => {
    formResendCallback.post(route('admin.merchants.resend-callback', merchant.value.id), {
        preserveScroll: true,
    });
};
</script>

<template>
    <div class="space-y-6">
        <div class="gap-8 grid grid-cols-1 2xl:grid-cols-7 xl:grid-cols-5">
            <div class="2xl:col-span-3 xl:col-span-2 space-y-6">
                <Card>
                    <template #title>
                        <h3 class="text-xl font-medium text-gray-900 dark:text-white">Магазин</h3>
                    </template>
                    <template #content>
                        <div class="space-y-3 text-sm">
                            <div class="flex items-center border-b dark:border-gray-700 pb-2 ">
                                <span class="font-medium text-gray-900 dark:text-gray-200 mr-auto">Название</span>
                                <span class="text-gray-500 dark:text-gray-400 truncate break-all text-right">{{ merchant.name }}</span>
                            </div>
                            <div class="flex items-center border-b dark:border-gray-700 pb-2 ">
                                <span class="font-medium text-gray-900 dark:text-gray-200 mr-auto">Описание</span>
                                <span class="text-gray-500 dark:text-gray-400 text-right break-all">{{ merchant.description }}</span>
                            </div>
                             <div class="flex items-center border-b dark:border-gray-700 pb-2 ">
                                <span class="font-medium text-gray-900 dark:text-gray-200 mr-auto">Домен</span>
                                <span class="text-gray-500 dark:text-gray-400 break-all text-right">{{ merchant.domain }}</span>
                            </div>
                            <div class="flex items-center border-b dark:border-gray-700 pb-2 ">
                                <span class="font-medium dark:text-gray-200 mr-auto">Статус</span>
                                <Tag :severity="merchant.active ? 'success' : 'danger'" :value="merchant.active ? 'Активен' : 'Остановлен'"></Tag>
                            </div>
                            <div v-if="viewStore.isAdminViewMode" class="flex items-center border-b dark:border-gray-700 pb-2 ">
                                <span class="font-medium text-gray-900 dark:text-gray-200 mr-auto">Владелец</span>
                                <span class="text-gray-500 dark:text-gray-400">{{ merchant.owner.email }}</span>
                            </div>
                            <div class="flex items-center pt-3">
                                <span class="font-medium text-gray-900 dark:text-gray-200 mr-auto">Merchant ID</span>
                                <CopyUUID :text="merchant.uuid"></CopyUUID>
                            </div>
                        </div>
                    </template>
                </Card>

                <Card v-if="viewStore.isAdminViewMode">
                    <template #title>
                         <h3 class="text-xl font-medium text-gray-900 dark:text-white">Модерация</h3>
                    </template>
                    <template #content>
                        <p class="mb-3 text-sm font-medium text-gray-500 dark:text-gray-300">
                            Разрешите работу мерчанта или заблокируйте его.
                        </p>
                        <div class="flex items-center justify-center mb-3">
                             <h1 class="text-gray-500 dark:text-gray-400 text-sm mr-3">Текущий статус:</h1>
                             <Tag v-if="! merchant.validated_at" severity="warning" value="На модерации" icon="pi pi-exclamation-triangle"></Tag>
                             <Tag v-else-if="merchant.banned_at" severity="danger" value="Заблокирован" icon="pi pi-ban"></Tag>
                             <Tag v-else-if="merchant.active" severity="success" value="Включен" icon="pi pi-check-circle"></Tag>
                             <Tag v-else severity="danger" value="Выключен" icon="pi pi-times-circle"></Tag>
                        </div>
                        <div class="flex justify-center mt-3 gap-2">
                             <Button
                                 @click="submitValidated"
                                 v-if="! merchant.validated_at"
                                 label="Разрешить"
                                 severity="success"
                                 size="small"
                                 :loading="formStatus.processing"
                             />
                             <Button
                                 @click="submitUnban"
                                 v-if="merchant.banned_at"
                                 label="Разблокировать"
                                 severity="info" 
                                 size="small"
                                 :loading="formStatus.processing"
                             />
                             <Button
                                 @click="submitBan"
                                 v-else
                                 label="Заблокировать"
                                 severity="danger"
                                 size="small"
                                 :loading="formStatus.processing"
                             />
                        </div>
                    </template>
                </Card>
            </div>

            <div class="2xl:col-span-4 xl:col-span-3 space-y-6">
                <Card>
                     <template #title>
                         <h3 class="text-xl font-medium text-gray-900 dark:text-white">Обработчик платежей</h3>
                    </template>
                    <template #content>
                         <p class="mb-5 text-sm font-medium text-gray-500 dark:text-gray-300">
                                Установите ссылку на Ваш обработчик для получения уведомлений. По ней мы будем отправлять POST запросы о статусах платежей.
                         </p>
                         <form class="space-y-4" @submit.prevent="submitCallback">
                                <div>
                                    <label for="callback_url" class="block mb-1 text-sm font-medium" :class="{'text-red-500': formCallback.errors.callback_url}">Укажите ссылку</label>
                                    <InputText
                                        id="callback_url"
                                        v-model="formCallback.callback_url"
                                        type="text"
                                        class="w-full"
                                        placeholder="https://example.com/callback"
                                        :invalid="!!formCallback.errors.callback_url"
                                        @input="formCallback.clearErrors('callback_url')"
                                        aria-describedby="callback-error"
                                    />
                                    <small id="callback-error" class="p-error" v-if="formCallback.errors.callback_url">{{ formCallback.errors.callback_url }}</small>
                                </div>

                                <Button
                                    type="submit"
                                    label="Сохранить"
                                    :loading="formCallback.processing"
                                    :icon="formCallback.recentlySuccessful ? 'pi pi-check' : ''"
                                />
                            </form>
                    </template>
                </Card>

                 <Card v-if="viewStore.isAdminViewMode">
                    <template #title>
                        <h3 class="text-xl font-medium text-gray-900 dark:text-white">Настройки для администратора</h3>
                    </template>
                    <template #content>
                         <form class="space-y-4" @submit.prevent="submitSettings">
                                <div>
                                    <label for="market" class="block mb-1 text-sm font-medium" :class="{'text-red-500': formSettings.errors.market}">Источник курсов (маркет)</label>
                                    <Dropdown
                                        id="market"
                                        v-model="formSettings.market"
                                        :options="markets"
                                        optionLabel="name" 
                                        optionValue="value"
                                        placeholder="Выберите маркет"
                                        class="w-full"
                                        :invalid="!!formSettings.errors.market"
                                        @change="formSettings.clearErrors('market')"
                                        aria-describedby="market-error"
                                    />
                                    <small id="market-error" class="p-error" v-if="formSettings.errors.market">{{ formSettings.errors.market }}</small>
                                </div>

                                <div>
                                    <label for="categories" class="block mb-1 text-sm font-medium" :class="{'text-red-500': formSettings.errors.categories}">Категории</label>
                                    <MultiSelect
                                        id="categories"
                                        v-model="formSettings.categories"
                                        :options="categories"
                                        optionLabel="name"
                                        optionValue="id"
                                        placeholder="Выберите категории"
                                        display="chip"
                                        class="w-full"
                                        :invalid="!!formSettings.errors.categories"
                                        @change="formSettings.clearErrors('categories')"
                                        aria-describedby="categories-error"
                                    />
                                    <small id="categories-error" class="p-error" v-if="formSettings.errors.categories">{{ formSettings.errors.categories }}</small>
                                </div>

                                <div>
                                     <label for="max_order_wait_time" class="block mb-1 text-sm font-medium" :class="{'text-red-500': formSettings.errors.max_order_wait_time}">Максимальное время ожидания выдачи реквизита (мс)</label>
                                     <InputNumber
                                        id="max_order_wait_time"
                                        v-model="formSettings.max_order_wait_time"
                                        inputId="max_order_wait_time_input"
                                        mode="decimal" 
                                        :min="1"
                                        placeholder="Введите время в миллисекундах (1 сек = 1000 мс)"
                                        class="w-full"
                                        inputClass="w-full"
                                        :invalid="!!formSettings.errors.max_order_wait_time"
                                        @input="formSettings.clearErrors('max_order_wait_time')"
                                        aria-describedby="wait-time-error wait-time-help"
                                    />
                                    <small id="wait-time-help" class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                        Примеры: 3000 мс = 3 секунды, 60000 мс = 1 минута
                                    </small>
                                    <small id="wait-time-error" class="p-error block" v-if="formSettings.errors.max_order_wait_time">{{ formSettings.errors.max_order_wait_time }}</small>
                                </div>

                                <div>
                                    <label class="block mb-1 text-sm font-medium">Минимальная и максимальная сумма сделки по валютам</label>
                                    <div class="flex gap-2 mb-2">
                                        <Dropdown
                                            v-model="selectedCurrency"
                                            :options="availableCurrencies()"
                                            optionLabel="name"
                                            optionValue="value"
                                            placeholder="Выберите валюту"
                                            class="w-full"
                                        />
                                        <Button
                                            type="button"
                                            label="Добавить"
                                            @click="() => { addMinOrderAmount(); addMaxOrderAmount(); }"
                                            :disabled="!selectedCurrency"
                                            severity="info"
                                            icon="pi pi-plus"
                                        />
                                    </div>
                                    <div v-if="Object.keys(minOrderAmounts).length > 0 || Object.keys(maxOrderAmounts).length > 0" class="mt-3 space-y-2">
                                        <div
                                            v-for="currency in Object.keys({...minOrderAmounts, ...maxOrderAmounts})"
                                            :key="currency"
                                            class="flex items-center gap-2 p-2 rounded-lg bg-gray-50 dark:bg-gray-700"
                                        >
                                            <div class="flex-1">
                                                <div class="text-sm font-medium text-gray-900 dark:text-gray-200 mb-1">
                                                    {{ currencies.find(c => c.value === currency)?.name || currency.toUpperCase() }}
                                                </div>
                                                <div class="flex items-center gap-2">
                                                    <InputNumber
                                                        v-model="minOrderAmounts[currency]"
                                                        mode="decimal"
                                                        :min="0"
                                                        :minFractionDigits="2"
                                                        :maxFractionDigits="8"
                                                        placeholder="Мин. сумма"
                                                        class="w-full"
                                                        inputClass="w-full"
                                                    />
                                                    <InputNumber
                                                        v-model="maxOrderAmounts[currency]"
                                                        mode="decimal"
                                                        :min="0"
                                                        :minFractionDigits="2"
                                                        :maxFractionDigits="8"
                                                        placeholder="Макс. сумма"
                                                        class="w-full"
                                                        inputClass="w-full"
                                                    />
                                                    <Button
                                                        type="button"
                                                        icon="pi pi-trash"
                                                        severity="danger"
                                                        text
                                                        rounded
                                                        @click.prevent="() => { removeMinOrderAmount(currency); removeMaxOrderAmount(currency); }"
                                                    />
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <p v-else class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                        Нет настроенных сумм. Добавьте валюту для настройки.
                                    </p>
                                    <small class="p-error block" v-if="formSettings.errors.min_order_amounts">{{ formSettings.errors.min_order_amounts }}</small>
                                    <small class="p-error block" v-if="formSettings.errors.max_order_amounts">{{ formSettings.errors.max_order_amounts }}</small>
                                </div>

                                <Button
                                    type="submit"
                                    label="Сохранить настройки"
                                    :loading="formSettings.processing"
                                    :icon="formSettings.recentlySuccessful ? 'pi pi-check' : ''"
                                />
                            </form>
                    </template>
                </Card>

                <Card v-if="viewStore.isAdminViewMode" class="mt-6"> <!-- Added mt-6 for spacing -->
                    <template #title>
                        <h3 class="text-xl font-medium text-gray-900 dark:text-white">Повторная отправка callback</h3>
                    </template>
                    <template #content>
                        <p class="mb-5 text-sm font-medium text-gray-500 dark:text-gray-300">
                                Выберите период дат для повторной отправки callback по всем сделкам мерчанта за указанный период.
                        </p>
                        <form class="space-y-4" @submit.prevent="submitResendCallback">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label for="start_date" class="block mb-1 text-sm font-medium" :class="{'text-red-500': formResendCallback.errors.start_date}">Дата начала</label>
                                        <Calendar
                                            id="start_date"
                                            v-model="formResendCallback.start_date"
                                            dateFormat="dd/mm/yy"
                                            placeholder="дд/мм/гггг"
                                            showIcon
                                            class="w-full"
                                            inputClass="w-full"
                                            :invalid="!!formResendCallback.errors.start_date"
                                            @change="formResendCallback.clearErrors('start_date')"
                                            aria-describedby="start-date-error"
                                        />
                                        <small id="start-date-error" class="p-error" v-if="formResendCallback.errors.start_date">{{ formResendCallback.errors.start_date }}</small>
                                    </div>
                                    <div>
                                        <label for="end_date" class="block mb-1 text-sm font-medium" :class="{'text-red-500': formResendCallback.errors.end_date}">Дата окончания</label>
                                         <Calendar
                                            id="end_date"
                                            v-model="formResendCallback.end_date"
                                            dateFormat="dd/mm/yy"
                                            placeholder="дд/мм/гггг"
                                            showIcon
                                            class="w-full"
                                            inputClass="w-full"
                                            :invalid="!!formResendCallback.errors.end_date"
                                            @change="formResendCallback.clearErrors('end_date')"
                                            aria-describedby="end-date-error"
                                        />
                                        <small id="end-date-error" class="p-error" v-if="formResendCallback.errors.end_date">{{ formResendCallback.errors.end_date }}</small>
                                    </div>
                                </div>
                                <small class="p-error block" v-if="formResendCallback.errors.date_range">{{ formResendCallback.errors.date_range }}</small>
                                <Button
                                    type="submit"
                                    :disabled="formResendCallback.processing"
                                    :loading="formResendCallback.processing"
                                    :icon="formResendCallback.recentlySuccessful ? 'pi pi-check' : ''"
                                >
                                    Отправить callback
                                </Button>
                            </form>
                    </template>
                </Card>
            </div>
        </div>

        <Gateways/>
    </div>
</template>

<style scoped>
/* Remove styles if not needed anymore */
</style>
