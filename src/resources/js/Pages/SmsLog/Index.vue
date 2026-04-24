<script setup>
import {Head, router, useForm} from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { usePage } from '@inertiajs/vue3';
import MainTableSection from "@/Wrappers/MainTableSection.vue";
import {useViewStore} from "@/store/view.js";
import ConfirmModal from "@/Components/Modals/ConfirmModal.vue";
import {useModalStore} from "@/store/modal.js";
import {ref, computed, onMounted, onUnmounted} from "vue";
import FiltersPanel from "@/Components/Filters/FiltersPanel.vue";
import InputFilter from "@/Components/Filters/Pertials/InputFilter.vue";
import FilterCheckbox from "@/Components/Filters/Pertials/FilterCheckbox.vue";
import DateTime from "@/Components/DateTime.vue";
import DisplayUUID from "@/Components/DisplayUUID.vue";
import GatewayLogo from "@/Components/GatewayLogo.vue";
import {useTableFiltersStore} from "@/store/tableFilters.js";
import SelectButton from 'primevue/selectbutton';
import InputText from 'primevue/inputtext';
import Chip from 'primevue/chip';
import Tag from 'primevue/tag';
import Button from 'primevue/button';

const modalStore = useModalStore();
const viewStore = useViewStore();
const smsLogs = usePage().props.smsLogs;
const smsLogsTotalCount = usePage().props.smsLogsTotalCount;
const senderStopList = usePage().props.senderStopList;
const smsStopWords = usePage().props.smsStopWords;
const currentTab = ref('logs');
const newStopWord = ref('');
const tableFiltersStore = useTableFiltersStore();

// --- Responsive Button Size Logic ---
const screenWidth = ref(window.innerWidth);
const updateScreenWidth = () => { screenWidth.value = window.innerWidth; };
onMounted(() => { window.addEventListener('resize', updateScreenWidth); });
onUnmounted(() => { window.removeEventListener('resize', updateScreenWidth); });
const selectButtonSize = computed(() => { return screenWidth.value < 768 ? 'small' : null; });
// --- End Responsive Button Size Logic ---

// --- Tab Options for SelectButton ---
const tabOptions = ref([
    { label: 'Сообщения', value: 'logs', icon: 'pi pi-envelope' },
    { label: 'Стоп-лист (отправители)', value: 'stop-list', icon: 'pi pi-ban' },
    { label: 'Стоп-слова', value: 'stop-words', icon: 'pi pi-align-justify' }
]);
// --- End Tab Options ---

const confirmAddSenderToStopLost = (smsLog) => {

    modalStore.openConfirmModal({
        title: `Добавить отправителя ${smsLog.sender} в стоп лист?`,
        body: `Все сообщения отправителя ${smsLog.sender} будут удалены, а новые сообщения будут игнорироваться.`,
        confirm_button_name: 'Подтвердить',
        confirm: () => {
            useForm({}).post(route('admin.sender-stop-list.store', smsLog.id), {
                preserveScroll: true,
                onFinish: () => {
                    router.visit(route('admin.sms-logs.index'))
                },
            });
        }
    });
};

const openPage = (tab) => {
    tableFiltersStore.setTab(tab);
    tableFiltersStore.setCurrentPage(1);

    router.visit(route(route().current()), {
        preserveScroll: true,
        data: tableFiltersStore.getQueryData,
    })
}

const deleteSenderFromStopList = (senderStopList) => {
    useForm({}).delete(route('admin.sender-stop-list.destroy', senderStopList.id), {
        preserveScroll: true,
        onFinish: () => {
            router.visit(route('admin.sms-logs.index'), {
                data: tableFiltersStore.getQueryData,
            })
        },
    });
}

const deleteSmsStopWord = (smsStopWord) => {
    useForm({}).delete(route('admin.sms-stop-word.destroy', smsStopWord.id), {
        preserveScroll: true,
        onFinish: () => {
            router.visit(route('admin.sms-logs.index'), {
                data: tableFiltersStore.getQueryData,
            })
        },
    });
}

const addSmsStopWord = () => {
    if (!newStopWord.value.trim()) return;

    useForm({
        word: newStopWord.value.trim()
    }).post(route('admin.sms-stop-word.store'), {
        preserveScroll: true,
        onFinish: () => {
            newStopWord.value = '';
            router.visit(route('admin.sms-logs.index'), {
                data: tableFiltersStore.getQueryData,
            })
        },
    });
}

onMounted(() => {
    // Логика onMounted для screenWidth
    window.addEventListener('resize', updateScreenWidth);

    // Логика onMounted для табов
    if (tableFiltersStore.getTab === '') {
        tableFiltersStore.setTab('logs');
    }
    currentTab.value = tableFiltersStore.getTab;
});

onUnmounted(() => {
    // Логика onUnmounted для screenWidth
    window.removeEventListener('resize', updateScreenWidth);
});

defineOptions({ layout: AuthenticatedLayout })
</script>

<template>
    <div>
        <Head title="Сообщения" />

        <MainTableSection
            title="Сообщения"
            :data="smsLogs"
            :display-pagination="currentTab === 'logs'"
        >
            <template v-slot:header>
                <div v-if="viewStore.isAdminViewMode" class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-4">
                    <SelectButton
                        v-model="currentTab"
                        :options="tabOptions"
                        optionLabel="label"
                        optionValue="value"
                        @change="openPage($event.value)"
                        aria-labelledby="sms-log-tabs"
                        :size="selectButtonSize"
                    >
                        <template #option="slotProps">
                            <div class="flex items-center">
                                <i :class="['pi', slotProps.option.icon, 'mr-2']"></i>
                                <span>{{ slotProps.option.label }}</span>
                            </div>
                        </template>
                    </SelectButton>
                    <div v-if="currentTab === 'logs' && viewStore.isAdminViewMode" class="mt-3 md:mt-0">
                        <Tag severity="info">Всего логов: {{ smsLogsTotalCount }}</Tag>
                    </div>
                </div>
            </template>
            <template v-slot:table-filters>
                <FiltersPanel name="sms-logs" v-if="currentTab === 'logs'">
                    <InputFilter
                        name="search"
                        placeholder="Поиск"
                        class="w-full sm:w-64"
                    />
                    <FilterCheckbox
                        v-if="viewStore.isAdminViewMode"
                        name="onlySuccessParsing"
                        title="Только зачисления"
                    />
                </FiltersPanel>
            </template>
            <template v-slot:body>
                <template v-if="currentTab === 'logs'">
                    <div class="relative overflow-x-auto shadow-md rounded-table ">
                        <table class="w-full text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400">
                            <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                            <tr>
                                <th scope="col" class="px-6 py-3">
                                    ID
                                </th>
                                <th scope="col" class="px-6 py-3">
                                    Отправитель
                                </th>
                                <th scope="col" class="px-6 py-3">
                                    Сообщение
                                </th>
                                <th scope="col" class="px-6 py-3" v-if="viewStore.isAdminViewMode">
                                    Парсинг
                                </th>
                                <th scope="col" class="px-6 py-3" v-if="viewStore.isAdminViewMode">
                                    Конверсия валют
                                </th>
                                <th scope="col" class="px-6 py-3">
                                    Тип
                                </th>
                                <th scope="col" class="px-6 py-3 text-nowrap">
                                    UUID сделки
                                </th>
                                <th scope="col" class="px-6 py-3">
                                    Устройство
                                </th>
                                <th scope="col" class="px-6 py-3" v-if="viewStore.isAdminViewMode">
                                    Трейдер
                                </th>
                                <th scope="col" class="px-6 py-3">
                                    Время
                                </th>
                            </tr>
                            </thead>
                            <tbody>
                            <tr v-for="sms_log in smsLogs.data" class="bg-white border-b last:border-none dark:bg-gray-800 dark:border-gray-700">
                                <th scope="row" class="px-6 py-3 font-medium whitespace-nowrap text-gray-900 dark:text-gray-200">
                                    {{ sms_log.id }}
                                </th>
                                <td class="px-6 py-3">
                                    <div class="flex justify-between items-center gap-2">
                                        <template v-if="!viewStore.isAdminViewMode">
                                            <div>{{ sms_log.sender }}</div>
                                        </template>
                                        <template v-else>
                                            <div class="flex items-center gap-3">
                                                <GatewayLogo v-if="sms_log.payment_gateway" :img_path="sms_log.payment_gateway.logo_path" class="w-10 h-10 text-gray-500 dark:text-gray-400"/>
                                                <div>
                                                    <div :class="{'text-green-500': sms_log.sender_exists}">
                                                        {{ sms_log.sender }}
                                                    </div>
                                                    <div v-if="sms_log.payment_gateway" class="text-nowrap text-xs">
                                                        {{ sms_log.payment_gateway.name }} ({{ sms_log.payment_gateway.currency }})
                                                    </div>
                                                </div>
                                            </div>
                                            <div v-if="!sms_log.sender_exists">
                                                <button
                                                    @click.prevent="confirmAddSenderToStopLost(sms_log)"
                                                    class="px-0 py-0 text-red-500 hover:text-red-600 flex items-center hover:underline"
                                                >
                                                    <svg class="w-4 h-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18 17.94 6M18 18 6.06 6"/>
                                                    </svg>
                                                </button>
                                            </div>
                                        </template>
                                    </div>
                                </td>
                                <td class="px-6 py-3">
                                    <div style="min-width: 200px;">{{ sms_log.message }}</div>
                                </td>
                                <td class="px-6 py-3" v-if="viewStore.isAdminViewMode">
                                    <div v-if="sms_log.parsing_result">
                                        <div v-if="sms_log.parsing_result.amount" class="flex gap-1">
                                            <div class="text-gray-900 dark:text-gray-200">Сумма:</div>
                                            <div>{{sms_log.parsing_result.amount}}</div>
                                        </div>
                                        <div v-if="sms_log.full_parsing_result?.currency" class="flex gap-1">
                                            <div class="text-gray-900 dark:text-gray-200">Валюта SMS:</div>
                                            <div class="font-semibold">{{ sms_log.full_parsing_result.currency }}</div>
                                        </div>
                                        <div v-if="sms_log.parsing_result.card" class="flex gap-1">
                                            <div class="text-gray-900 dark:text-gray-200">Карта:</div>
                                            <div>*{{sms_log.parsing_result.card}}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-3" v-if="viewStore.isAdminViewMode">
                                    <div v-if="sms_log.conversion_info" class="text-xs space-y-1">
                                        <div v-if="sms_log.conversion_info.error" class="text-red-500">
                                            {{ sms_log.conversion_info.error }}
                                        </div>
                                        
                                        <template v-else>
                                            <div class="bg-blue-50 dark:bg-blue-900/20 p-2 rounded">
                                                <div class="font-semibold text-blue-800 dark:text-blue-300 mb-1">
                                                    💱 {{ sms_log.conversion_info.gateway_currency }} → {{ sms_log.conversion_info.sms_currency }}
                                                </div>
                                                <div class="text-gray-600 dark:text-gray-400">
                                                    100{{ sms_log.conversion_info.gateway_currency }} → {{ sms_log.conversion_info.conversion_examples['100_to_sms_currency'] }} {{ sms_log.conversion_info.sms_currency }}
                                                </div>
                                                <div class="text-gray-600 dark:text-gray-400">
                                                    1000{{ sms_log.conversion_info.gateway_currency }} → {{ sms_log.conversion_info.conversion_examples['1000_to_sms_currency'] }} {{ sms_log.conversion_info.sms_currency }}
                                                </div>
                                            </div>
                                            
                                            <div v-if="sms_log.conversion_info.order_conversion" class="mt-2 p-2 rounded" 
                                                 :class="sms_log.conversion_info.order_conversion.within_tolerance ? 'bg-green-50 dark:bg-green-900/20' : 'bg-red-50 dark:bg-red-900/20'">
                                                <div class="font-semibold mb-1"
                                                     :class="sms_log.conversion_info.order_conversion.within_tolerance ? 'text-green-800 dark:text-green-300' : 'text-red-800 dark:text-red-300'">
                                                    {{ sms_log.conversion_info.order_conversion.within_tolerance ? '✅ Заказ закроется' : '❌ Заказ НЕ закроется' }}
                                                </div>
                                                <div class="text-gray-600 dark:text-gray-400">
                                                    Заказ: {{ sms_log.conversion_info.order_conversion.order_amount }} {{ sms_log.conversion_info.order_conversion.order_currency }}
                                                </div>
                                                <div class="text-gray-600 dark:text-gray-400">
                                                    Конвертировано: {{ sms_log.conversion_info.order_conversion.converted_amount }} {{ sms_log.conversion_info.sms_currency }}
                                                </div>
                                                <div class="text-gray-600 dark:text-gray-400">
                                                    SMS: {{ sms_log.conversion_info.sms_amount }} {{ sms_log.conversion_info.sms_currency }}
                                                </div>
                                                <div class="text-gray-600 dark:text-gray-400">
                                                    Разница: {{ sms_log.conversion_info.order_conversion.difference }} {{ sms_log.conversion_info.sms_currency }} 
                                                    ({{ sms_log.conversion_info.order_conversion.percent_difference }}%)
                                                </div>
                                                <div class="text-gray-600 dark:text-gray-400">
                                                    Допустимо: ±{{ sms_log.conversion_info.order_conversion.tolerance }}%
                                                </div>
                                            </div>
                                        </template>
                                    </div>
                                    <div v-else class="text-gray-400 text-xs">
                                        Валюты совпадают
                                    </div>
                                </td>
                                <td class="px-6 py-3">
                                    {{ sms_log.type }}
                                </td>
                                <td class="px-6 py-3">
                                    <DisplayUUID v-if="sms_log.order?.uuid" :uuid="sms_log.order?.uuid"/>
                                </td>
                                <td class="px-6 py-3 text-nowrap">
                                    {{ sms_log.device?.name }}
                                </td>
                                <td class="px-6 py-3" v-if="viewStore.isAdminViewMode">
                                    {{ sms_log.user.email }}
                                </td>
                                <td class="px-6 py-3 text-nowrap">
                                    <DateTime :data="sms_log.created_at"></DateTime>
                                </td>
                            </tr>
                            </tbody>
                        </table>
                    </div>
                </template>
                <template v-else-if="currentTab === 'stop-list'">
                    <div class="flex flex-wrap gap-2">
                        <Chip
                            v-for="item in senderStopList"
                            :key="item.id"
                            :label="item.sender"
                            removable
                            @remove="deleteSenderFromStopList(item)"
                            class="text-sm"
                        />
                    </div>
                </template>
                <template v-else-if="currentTab === 'stop-words'">
                    <div class="mb-5">
                        <div class="flex items-center gap-2 mb-4">
                            <InputText
                                type="text"
                                v-model="newStopWord"
                                placeholder="Добавить стоп-слово"
                                class="w-52"
                                :size="selectButtonSize"
                            />
                            <Button
                                label="Добавить"
                                icon="pi pi-plus"
                                @click="addSmsStopWord"
                                :size="selectButtonSize"
                            />
                        </div>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
                            Стоп-слова используются для фильтрации SMS сообщений. Сообщения, содержащие эти слова, будут игнорироваться при парсинге.
                        </p>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <Chip
                            v-for="item in smsStopWords"
                            :key="item.id"
                            :label="item.word"
                            removable
                            @remove="deleteSmsStopWord(item)"
                            class="text-sm bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300"
                        />
                    </div>
                </template>
            </template>
        </MainTableSection>

        <ConfirmModal/>
    </div>
</template>
