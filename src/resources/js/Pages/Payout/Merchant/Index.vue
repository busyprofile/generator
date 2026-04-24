<script setup>
import {Head, router} from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { usePage } from '@inertiajs/vue3';
import IsActiveStatus from "@/Components/IsActiveStatus.vue";
import MainTableSection from "@/Wrappers/MainTableSection.vue";
import EditAction from "@/Components/Table/EditAction.vue";
import AddMobileIcon from "@/Components/AddMobileIcon.vue";
import {computed, onMounted, ref} from "vue";
import DateTime from "@/Components/DateTime.vue";
import PayoutStatus from "@/Components/PayoutStatus.vue";
import PayoutModal from "@/Modals/PayoutModal.vue";
import {useModalStore} from "@/store/modal.js";
import ShowAction from "@/Components/Table/ShowAction.vue";

const payouts = usePage().props.payouts;
const payoutGateways = usePage().props.payoutGateways;
const statistics = usePage().props.statistics;
const currentTab = ref('payouts');
const modalStore = useModalStore();

const filtersData = ref(usePage().props.filtersData);
const currentFilters = ref(usePage().props.currentFilters);

const openPage = (tab) => {
    currentTab.value = tab;

    let data = {
        tab: tab,
        page: 1
    };

    router.visit(route(route().current()), {
        preserveScroll: true,
        data: data,
    })
}

const tableData = computed(() => {
    return  currentTab.value === 'payouts' ? payouts : payoutGateways;
})

const payoutGatewaysSelected = computed(() => {
    return filtersData.value.payout_gateways.map(item => {
        item.selected = currentFilters.value.payout_gateways.includes(item.id.toString());

        return item;
    })
})

const payoutGatewaysSelectedCount = computed(() => {
    return payoutGatewaysSelected.value.filter(i => i.selected).length
})

const payoutStatusesSelected = computed(() => {
    return filtersData.value.payout_statuses.map(i => {
        i.selected = currentFilters.value.payout_statuses.includes(i.value);

        return i;
    })
})

const payoutStatusesSelectedCount = computed(() => {
    return payoutStatusesSelected.value.filter(i => i.selected).length
})

const filters = computed(() => {
    return {
        payout_gateways: payoutGatewaysSelected.value.filter(i => i.selected).map(i => i.id).join(','),
        statuses: payoutStatusesSelected.value.filter(i => i.selected).map(i => i.value).join(','),
    }
})

const applyFilters = () => {
    router.visit(route(route().current()), {
        data: {
            filters: filters.value,
            page: 1
        },
        preserveScroll: true
    })
}

onMounted(() => {
    let urlParams = new URLSearchParams(window.location.search);
    currentTab.value = urlParams.get('tab') ?? 'payouts'
})

defineOptions({ layout: AuthenticatedLayout })
</script>

<template>
    <div>
        <Head title="Выплаты" />

        <MainTableSection
            title="Выплаты"
            :data="tableData"
        >
            <template v-slot:button>
                <!-- <button
                    @click="router.visit(route('payout-gateways.create'))"
                    type="button"
                    class="hidden md:block text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:ring-blue-300 font-medium rounded-xl  text-sm px-5 py-2.5 dark:bg-blue-600 dark:hover:bg-blue-700 focus:outline-none dark:focus:ring-blue-800"
                >
                    Создать направление
                </button> -->
                <AddMobileIcon
                    @click="router.visit(route('payout-gateways.create'))"
                />
            </template>
            <template v-slot:header>
                <ul class="flex flex-wrap text-sm font-medium text-center text-gray-500 dark:text-gray-400">
                    <li class="me-2">
                        <a @click.prevent="openPage('payouts')" href="#" :class="currentTab === 'payouts' ? 'shadow inline-flex items-center px-4 py-2 text-white bg-blue-600 rounded-xl active' : 'border border-gray-200 dark:border-gray-700 inline-flex items-center px-4 py-2 rounded-xl hover:text-gray-900 hover:bg-gray-100 dark:hover:bg-gray-800 dark:hover:text-white'" aria-current="page">
                            <svg class="w-4 h-4 sm:mr-2 mr-0" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m11.0001 18-.8536-.8536c-.0937-.0937-.1464-.2209-.1464-.3535v-4.4172c0-.2422-.08794-.4762-.24744-.6585L4.45127 5.6585C3.88551 5.01192 4.34469 4 5.20385 4H18.7547c.8658 0 1.3225 1.02544.7433 1.66896L16.5001 9m-2.5 9.3754c.3347.3615.7824.6134 1.2788.7195.4771.1584 1.0002.1405 1.464-.05.4638-.1906.8338-.5396 1.0356-.977.2462-.8286-.6363-1.7337-1.7735-1.9948-1.1372-.2611-2.016-1.1604-1.7735-1.9948.2016-.4375.5716-.7868 1.0354-.9774.4639-.1905.9871-.2082 1.4643-.0496.491.1045.9348.3517 1.2689.7067m-1.9397 5.41V20m0-8v.9771"/>
                            </svg>
                            <span class="sm:block hidden">Выплаты</span>
                        </a>
                    </li>
                    <li class="me-2">
                        <a @click.prevent="openPage('payout-gateways')" href="#" :class="currentTab === 'payout-gateways' ? 'shadow inline-flex items-center px-4 py-2 text-white bg-blue-600 rounded-xl active' : 'border border-gray-200 dark:border-gray-700 inline-flex items-center px-4 py-2 rounded-xl hover:text-gray-900 hover:bg-gray-100 dark:hover:bg-gray-800 dark:hover:text-white'" aria-current="page">
                            <svg class="w-4 h-4 sm:mr-2 mr-0" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 12v4m0 0a2 2 0 1 0 0 4 2 2 0 0 0 0-4ZM8 8a2 2 0 1 0 0-4 2 2 0 0 0 0 4Zm0 0v2a2 2 0 0 0 2 2h4a2 2 0 0 0 2-2V8m0 0a2 2 0 1 0 0-4 2 2 0 0 0 0 4Z"/>
                            </svg>
                            <span class="sm:block hidden">Мои направления</span>
                        </a>
                    </li>
                    <li class="me-2">
                        <a @click.prevent="openPage('statistics')" href="#" :class="currentTab === 'statistics' ? 'shadow inline-flex items-center px-4 py-2 text-white bg-blue-600 rounded-xl active' : 'border border-gray-200 dark:border-gray-700 inline-flex items-center px-4 py-2 rounded-xl hover:text-gray-900 hover:bg-gray-100 dark:hover:bg-gray-800 dark:hover:text-white'" aria-current="page">
                            <svg class="w-4 h-4 sm:mr-2 mr-0" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 15v4m6-6v6m6-4v4m6-6v6M3 11l6-5 6 5 5.5-5.5"/>
                            </svg>
                            <span class="sm:block hidden">Статистика</span>
                        </a>
                    </li>
                </ul>
            </template>
            <template v-slot:table-filters>
                <section v-if="currentTab === 'payouts'" class="flex items-center mb-5">
                    <div class="mx-auto w-full">
                        <div class="relative bg-white shadow-md dark:bg-gray-800 sm:rounded-table">
                            <div class="flex flex-col xl:items-center justify-between p-2 space-y-3 lg:flex-row lg:space-y-0 lg:space-x-4">
                                <div class="lg:flex items-center gap-4 lg:space-y-0 space-y-3">
                                    <div class="flex items-center w-full space-x-3 lg:w-auto">
                                        <button id="filterDropdownButton" data-dropdown-toggle="payoutGatewayFilterDropdown" class="flex items-center justify-center w-full px-4 py-2 text-sm font-medium text-gray-900 bg-white border border-gray-200 rounded-xl lg:w-auto focus:outline-none hover:bg-gray-100 hover:text-primary-700 focus:z-10 focus:ring-4 focus:ring-gray-200 dark:focus:ring-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:border-gray-600 dark:hover:text-white dark:hover:bg-gray-700" type="button">
                                            <span v-if="payoutGatewaysSelectedCount" class="inline-flex items-center justify-center w-4 h-4 mr-2 text-xs font-semibold text-blue-800 bg-blue-200 rounded-full">
                                                {{ payoutGatewaysSelectedCount }}
                                            </span>
                                            Направление
                                            <svg class="-mr-1 ml-1.5 w-5 h-5" fill="currentColor" viewbox="0 0 20 20" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                                <path clip-rule="evenodd" fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" />
                                            </svg>
                                        </button>
                                        <!-- Dropdown menu -->
                                        <div id="payoutGatewayFilterDropdown" class="z-10 hidden w-48 p-3 bg-white rounded-xl shadow dark:bg-gray-700">
                                            <h6 class="mb-3 text-sm font-medium text-gray-900 dark:text-white">
                                                Мои направления
                                            </h6>
                                            <ul class="space-y-2 text-sm" aria-labelledby="dropdownDefault">
                                                <li
                                                    v-for="payoutGateway in payoutGatewaysSelected"
                                                    class="flex items-center"
                                                >
                                                    <input
                                                        :id="`payoutGateway-${payoutGateway.id}`"
                                                        type="checkbox"
                                                        :value="payoutGateway.id"
                                                        v-model="payoutGateway.selected"
                                                        class="w-4 h-4 bg-gray-100 border-gray-300 rounded text-primary-600 focus:ring-primary-500 dark:focus:ring-primary-600 dark:ring-offset-gray-700 focus:ring-2 dark:bg-gray-600 dark:border-gray-500"
                                                    />
                                                    <label :for="`payoutGateway-${payoutGateway.id}`" class="ml-2 text-sm font-medium text-gray-900 dark:text-gray-100">
                                                        {{ payoutGateway.name }}
                                                    </label>
                                                </li>
                                            </ul>
                                        </div>
                                    </div>
                                    <div class="flex items-center w-full space-x-3 lg:w-auto">
                                        <button id="filterDropdownButton" data-dropdown-toggle="payoutStatusFilterDropdown" class="flex items-center justify-center w-full px-4 py-2 text-sm font-medium text-gray-900 bg-white border border-gray-200 rounded-xl lg:w-auto focus:outline-none hover:bg-gray-100 hover:text-primary-700 focus:z-10 focus:ring-4 focus:ring-gray-200 dark:focus:ring-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:border-gray-600 dark:hover:text-white dark:hover:bg-gray-700" type="button">
                                            <span v-if="payoutStatusesSelectedCount" class="inline-flex items-center justify-center w-4 h-4 mr-2 text-xs font-semibold text-blue-800 bg-blue-200 rounded-full">
                                                {{ payoutStatusesSelectedCount }}
                                            </span>
                                            Статус
                                            <svg class="-mr-1 ml-1.5 w-5 h-5" fill="currentColor" viewbox="0 0 20 20" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                                <path clip-rule="evenodd" fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" />
                                            </svg>
                                        </button>
                                        <!-- Dropdown menu -->
                                        <div id="payoutStatusFilterDropdown" class="z-10 hidden w-48 p-3 bg-white rounded-xl shadow dark:bg-gray-700">
                                            <h6 class="mb-3 text-sm font-medium text-gray-900 dark:text-white">
                                                Статусы
                                            </h6>
                                            <ul class="space-y-2 text-sm" aria-labelledby="dropdownDefault">
                                                <li
                                                    v-for="payoutStatus in payoutStatusesSelected"
                                                    class="flex items-center"
                                                >
                                                    <input
                                                        :id="`payoutStatus-${payoutStatus.value}`"
                                                        type="checkbox"
                                                        :value="payoutStatus.value"
                                                        v-model="payoutStatus.selected"
                                                        class="w-4 h-4 bg-gray-100 border-gray-300 rounded text-primary-600 focus:ring-primary-500 dark:focus:ring-primary-600 dark:ring-offset-gray-700 focus:ring-2 dark:bg-gray-600 dark:border-gray-500"
                                                    />
                                                    <label :for="`payoutStatus-${payoutStatus.value}`" class="ml-2 text-sm font-medium text-gray-900 dark:text-gray-100">
                                                        {{ payoutStatus.name }}
                                                    </label>
                                                </li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                                <div class="flex items-center">
                                    <button
                                        type="button"
                                        class="w-full text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:ring-blue-300 font-medium rounded-xl text-sm px-5 py-2.5 h-[38px] dark:bg-blue-600 dark:hover:bg-blue-700 focus:outline-none dark:focus:ring-blue-800"
                                        @click.prevent="applyFilters"
                                    >
                                        Фильтровать
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
            </template>
            <template v-slot:body>
                <div class="relative overflow-x-auto shadow-md sm:rounded-table">
                    <table v-if="currentTab === 'payouts'" class="w-full text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400">
                        <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                        <tr>
                            <th scope="col" class="px-6 py-3">
                                ID
                            </th>
                            <th scope="col" class="px-6 py-3">
                                Выплата
                            </th>
                            <th scope="col" class="px-6 py-3">
                                Списание
                            </th>
                            <th scope="col" class="px-6 py-3">
                                Комиссия
                            </th>
                            <th scope="col" class="px-6 py-3">
                                Статус
                            </th>
                            <th scope="col" class="px-6 py-3">
                                Создан
                            </th>
                            <th scope="col" class="px-6 py-3 flex justify-center">
                                <span class="sr-only">Действия</span>
                            </th>
                        </tr>
                        </thead>
                        <tbody>
                        <tr v-for="payout in payouts.data" class="bg-white border-b last:border-none dark:bg-gray-800 dark:border-gray-700">
                            <th scope="row" class="px-6 py-3 font-medium whitespace-nowrap text-gray-900 dark:text-gray-200">#{{ payout.id }}</th>
                            <td class="px-6 py-3">
                                <div class="text-nowrap">{{ payout.payout_amount }} {{ payout.currency.toUpperCase() }}</div>
                            </td>
                            <td class="px-6 py-3">
                                <div class="text-nowrap">{{ payout.liquidity_amount }} {{ payout.liquidity_currency.toUpperCase() }}</div>
                            </td>
                            <td class="px-6 py-3">
                                <div class="text-nowrap">{{ payout.service_commission_amount }} {{ payout.liquidity_currency.toUpperCase() }}</div>
                            </td>
                            <td class="px-6 py-3">
                                <PayoutStatus :status="payout.status" :status_name="payout.status_name"></PayoutStatus>
                            </td>
                            <td class="px-6 py-3">
                                <DateTime class="justify-center" :data="payout.created_at"/>
                            </td>
                            <td class="px-6 py-3 text-right">
                                <ShowAction @click.prevent="modalStore.openPayoutModal({payout})"></ShowAction>
                            </td>
                        </tr>
                        </tbody>
                    </table>
                    <table v-if="currentTab === 'payout-gateways'" class="w-full text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400">
                        <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                        <tr>
                            <th scope="col" class="px-6 py-3">
                                ID
                            </th>
                            <th scope="col" class="px-6 py-3">
                                Название
                            </th>
                            <th scope="col" class="px-6 py-3 text-nowrap">
                                Payout Gateway ID
                            </th>
                            <th scope="col" class="px-6 py-3 text-nowrap">
                                Оборот
                            </th>
                            <th scope="col" class="px-6 py-3">
                                Статус
                            </th>
                            <th scope="col" class="px-6 py-3 flex justify-center">
                                <span class="sr-only">Действия</span>
                            </th>
                        </tr>
                        </thead>
                        <tbody>
                        <tr v-for="payoutGateway in payoutGateways.data" class="bg-white border-b last:border-none dark:bg-gray-800 dark:border-gray-700">
                            <th scope="row" class="px-6 py-3 font-medium whitespace-nowrap text-gray-900 dark:text-gray-200">{{ payoutGateway.id }}</th>
                            <td class="px-6 py-3">
                                {{ payoutGateway.name }}
                            </td>
                            <td class="px-6 py-3">
                                {{ payoutGateway.uuid }}
                            </td>
                            <td class="px-6 py-3 text-nowrap">
                                {{ payoutGateway.total_liquidity.amount }} {{ payoutGateway.total_liquidity.currency.toUpperCase() }}
                            </td>
                            <td class="px-6 py-3">
                                <IsActiveStatus :is_active="payoutGateway.enabled"></IsActiveStatus>
                            </td>
                            <td class="px-6 py-3 text-right">
                                <div class="flex justify-center gap-2">
                                    <EditAction :link="route('payout-gateways.edit', payoutGateway.id)"></EditAction>
                                </div>
                            </td>
                        </tr>
                        </tbody>
                    </table>
                </div>
                <div v-if="currentTab === 'statistics'" class="mx-auto text-center">
                    <div class="bg-white dark:bg-gray-800 shadow-md rounded-plate grid max-w-full mx-auto text-gray-900 xl:grid-cols-4 md:grid-cols-2 sm:grid-cols-1 dark:text-white">
                        <div class="xl:border-b-0 border-b md:border-r border-gray-200 dark:border-gray-700 py-5 flex flex-col items-center justify-center">
                            <div class="mb-2 text-3xl md:text-3xl font-extrabold">
                                {{ statistics.completed_payouts.amount }}
                                <span class="text-xl text-gray-500 dark:text-gray-400">
                                    {{ statistics.completed_payouts.currency.toUpperCase() }}
                                </span>
                            </div>
                            <div class="font-light text-gray-500 dark:text-gray-400">Выплачено</div>
                            <div class="flex mt-1 font-light text-xs text-gray-900 dark:text-gray-200 border border-gray-200 dark:border-gray-600 rounded-xl p-1 px-2">
                                <svg class="w-4 h-4 mr-1 text-blue-500" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m8.032 12 1.984 1.984 4.96-4.96m4.55 5.272.893-.893a1.984 1.984 0 0 0 0-2.806l-.893-.893a1.984 1.984 0 0 1-.581-1.403V7.04a1.984 1.984 0 0 0-1.984-1.984h-1.262a1.983 1.983 0 0 1-1.403-.581l-.893-.893a1.984 1.984 0 0 0-2.806 0l-.893.893a1.984 1.984 0 0 1-1.403.581H7.04A1.984 1.984 0 0 0 5.055 7.04v1.262c0 .527-.209 1.031-.581 1.403l-.893.893a1.984 1.984 0 0 0 0 2.806l.893.893c.372.372.581.876.581 1.403v1.262a1.984 1.984 0 0 0 1.984 1.984h1.262c.527 0 1.031.209 1.403.581l.893.893a1.984 1.984 0 0 0 2.806 0l.893-.893a1.985 1.985 0 0 1 1.403-.581h1.262a1.984 1.984 0 0 0 1.984-1.984V15.7c0-.527.209-1.031.581-1.403Z"/>
                                </svg>
                                Выплат {{ statistics.completed_payouts.count }}
                            </div>
                        </div>
                        <div class="xl:border-b-0 border-b lg:border-r border-gray-200 dark:border-gray-700 py-5 flex flex-col items-center justify-center">
                            <div class="mb-2 text-3xl md:text-3xl font-extrabold">
                                {{ statistics.commission.amount }}
                                <span class="text-xl text-gray-500 dark:text-gray-400">
                                {{ statistics.commission.currency.toUpperCase() }}
                            </span>
                            </div>
                            <div class="font-light text-gray-500 dark:text-gray-400">Комиссия</div>
                            <div class="flex mt-1 font-light text-xs text-gray-900 dark:text-gray-200 border border-gray-200 dark:border-gray-600 rounded-xl p-1 px-2">
                                <svg class="w-4 h-4 mr-1 text-blue-500" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m8.032 12 1.984 1.984 4.96-4.96m4.55 5.272.893-.893a1.984 1.984 0 0 0 0-2.806l-.893-.893a1.984 1.984 0 0 1-.581-1.403V7.04a1.984 1.984 0 0 0-1.984-1.984h-1.262a1.983 1.983 0 0 1-1.403-.581l-.893-.893a1.984 1.984 0 0 0-2.806 0l-.893.893a1.984 1.984 0 0 1-1.403.581H7.04A1.984 1.984 0 0 0 5.055 7.04v1.262c0 .527-.209 1.031-.581 1.403l-.893.893a1.984 1.984 0 0 0 0 2.806l.893.893c.372.372.581.876.581 1.403v1.262a1.984 1.984 0 0 0 1.984 1.984h1.262c.527 0 1.031.209 1.403.581l.893.893a1.984 1.984 0 0 0 2.806 0l.893-.893a1.985 1.985 0 0 1 1.403-.581h1.262a1.984 1.984 0 0 0 1.984-1.984V15.7c0-.527.209-1.031.581-1.403Z"/>
                                </svg>
                                Выплат {{ statistics.commission.count }}
                            </div>
                        </div>
                        <div class="md:border-b-0 border-b md:border-r border-gray-200 dark:border-gray-700 py-5 flex flex-col items-center justify-center">
                            <div class="mb-2 text-3xl md:text-3xl font-extrabold">
                                {{ statistics.canceled_payouts.amount }}
                                <span class="text-xl text-gray-500 dark:text-gray-400">
                                {{ statistics.canceled_payouts.currency.toUpperCase() }}
                            </span>
                            </div>
                            <div class="font-light text-gray-500 dark:text-gray-400">Отменено</div>
                            <div class="flex mt-1 font-light text-xs text-gray-900 dark:text-gray-200 border border-gray-200 dark:border-gray-600 rounded-xl p-1 px-2">
                                <svg class="w-4 h-4 mr-1 text-blue-500" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m8.032 12 1.984 1.984 4.96-4.96m4.55 5.272.893-.893a1.984 1.984 0 0 0 0-2.806l-.893-.893a1.984 1.984 0 0 1-.581-1.403V7.04a1.984 1.984 0 0 0-1.984-1.984h-1.262a1.983 1.983 0 0 1-1.403-.581l-.893-.893a1.984 1.984 0 0 0-2.806 0l-.893.893a1.984 1.984 0 0 1-1.403.581H7.04A1.984 1.984 0 0 0 5.055 7.04v1.262c0 .527-.209 1.031-.581 1.403l-.893.893a1.984 1.984 0 0 0 0 2.806l.893.893c.372.372.581.876.581 1.403v1.262a1.984 1.984 0 0 0 1.984 1.984h1.262c.527 0 1.031.209 1.403.581l.893.893a1.984 1.984 0 0 0 2.806 0l.893-.893a1.985 1.985 0 0 1 1.403-.581h1.262a1.984 1.984 0 0 0 1.984-1.984V15.7c0-.527.209-1.031.581-1.403Z"/>
                                </svg>
                                Выплат {{ statistics.canceled_payouts.count }}
                            </div>
                        </div>
                        <div class="flex flex-col items-center justify-center py-5">
                            <div class="mb-2 text-3xl md:text-3xl font-extrabold">
                                {{ statistics.total.amount }}
                                <span class="text-xl text-gray-500 dark:text-gray-400">
                                {{ statistics.total.currency.toUpperCase() }}
                            </span>
                            </div>
                            <div class="font-light text-gray-500 dark:text-gray-400">Всего</div>
                            <div class="flex mt-1 font-light text-xs text-gray-900 dark:text-gray-200 border border-gray-200 dark:border-gray-600 rounded-xl p-1 px-2">
                                <svg class="w-4 h-4 mr-1 text-blue-500" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m8.032 12 1.984 1.984 4.96-4.96m4.55 5.272.893-.893a1.984 1.984 0 0 0 0-2.806l-.893-.893a1.984 1.984 0 0 1-.581-1.403V7.04a1.984 1.984 0 0 0-1.984-1.984h-1.262a1.983 1.983 0 0 1-1.403-.581l-.893-.893a1.984 1.984 0 0 0-2.806 0l-.893.893a1.984 1.984 0 0 1-1.403.581H7.04A1.984 1.984 0 0 0 5.055 7.04v1.262c0 .527-.209 1.031-.581 1.403l-.893.893a1.984 1.984 0 0 0 0 2.806l.893.893c.372.372.581.876.581 1.403v1.262a1.984 1.984 0 0 0 1.984 1.984h1.262c.527 0 1.031.209 1.403.581l.893.893a1.984 1.984 0 0 0 2.806 0l.893-.893a1.985 1.985 0 0 1 1.403-.581h1.262a1.984 1.984 0 0 0 1.984-1.984V15.7c0-.527.209-1.031.581-1.403Z"/>
                                </svg>
                                Выплат {{ statistics.total.count }}
                            </div>
                        </div>
                    </div>
                </div>
            </template>
        </MainTableSection>

        <PayoutModal/>
    </div>
</template>
