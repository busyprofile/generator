<script setup>
import {Head, Link, router} from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { usePage } from '@inertiajs/vue3';
import IsActiveStatus from "@/Components/IsActiveStatus.vue";
import MainTableSection from "@/Wrappers/MainTableSection.vue";
import EditAction from "@/Components/Table/EditAction.vue";
import AddMobileIcon from "@/Components/AddMobileIcon.vue";
import {computed, onMounted, ref} from "vue";
import ShowAction from "@/Components/Table/ShowAction.vue";
import PayoutStatus from "@/Components/PayoutStatus.vue";
import DateTime from "@/Components/DateTime.vue";
import PayoutModal from "@/Modals/PayoutModal.vue";
import {useModalStore} from "@/store/modal.js";
import PaymentDetail from "@/Components/PaymentDetail.vue";

const payouts = usePage().props.payouts;
const payoutOffers = usePage().props.payoutOffers;
const totalFundsOnHold = usePage().props.totalFundsOnHold;
const totalTurnover = usePage().props.totalTurnover;
const currentTab = ref('payouts');
const modalStore = useModalStore();

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
    return  currentTab.value === 'payouts' ? payouts : payoutOffers;
})

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
                    @click="router.visit(route('trader.payout-offers.create'))"
                    type="button"
                    class="hidden md:block text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:ring-blue-300 font-medium rounded-xl  text-sm px-5 py-2.5 dark:bg-blue-600 dark:hover:bg-blue-700 focus:outline-none dark:focus:ring-blue-800"
                >
                    Создать предложение
                </button> -->
                <AddMobileIcon
                    @click="router.visit(route('trader.payout-offers.create'))"
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
                        <a @click.prevent="openPage('payout-offers')" href="#" :class="currentTab === 'payout-offers' ? 'shadow inline-flex items-center px-4 py-2 text-white bg-blue-600 rounded-xl active' : 'border border-gray-200 dark:border-gray-700 inline-flex items-center px-4 py-2 rounded-xl hover:text-gray-900 hover:bg-gray-100 dark:hover:bg-gray-800 dark:hover:text-white'" aria-current="page">
                            <svg class="w-4 h-4 sm:mr-2 mr-0" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 6H5m2 3H5m2 3H5m2 3H5m2 3H5m11-1a2 2 0 0 0-2-2h-2a2 2 0 0 0-2 2M7 3h11a1 1 0 0 1 1 1v16a1 1 0 0 1-1 1H7a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1Zm8 7a2 2 0 1 1-4 0 2 2 0 0 1 4 0Z"/>
                            </svg>
                            <span class="sm:block hidden">Мои предложения</span>
                        </a>
                    </li>
                </ul>
            </template>
            <template v-slot:body>
                <div v-if="currentTab === 'payouts'" class="flex gap-5">
                    <div class="text-base text-gray-500 dark:text-gray-400 mb-3">
                        Оборот:
                        <span class="font-semibold text-gray-900 dark:text-gray-200 mr-1">
                        {{ totalTurnover.amount }}
                        </span>
                        <span class="text-sm font-semibold">
                        {{ totalTurnover.currency.toUpperCase() }}
                    </span>
                    </div>
                    <div class="text-base text-gray-500 dark:text-gray-400 mb-3">
                        Холд:
                        <span class="font-semibold text-gray-900 dark:text-gray-200 mr-1">
                            {{ totalFundsOnHold.amount }}
                        </span>
                        <span class="text-sm font-semibold">
                            {{ totalFundsOnHold.currency.toUpperCase() }}
                        </span>
                    </div>
                </div>
                <div class="relative overflow-x-auto shadow-md sm:rounded-table">
                    <table v-if="currentTab === 'payouts'" class="w-full text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400">
                        <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                        <tr>
                            <th scope="col" class="px-6 py-3">
                                ID
                            </th>
                            <th scope="col" class="px-6 py-3">
                                Сумма
                            </th>
                            <th scope="col" class="px-6 py-3">
                                Получатель
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
                                <div class="flex items-center gap-2">
                                    <div v-if="payout.funds_on_hold?.is_on_hold">
                                        <svg class="w-4 h-4 text-primary" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                                        </svg>
                                    </div>
                                    <div>
                                        <div class="text-nowrap text-gray-900 dark:text-gray-200">{{ payout.payout_amount }} {{ payout.currency.toUpperCase() }}</div>
                                        <div class="text-nowrap text-xs">{{ payout.trader_profit_amount }} {{ payout.liquidity_currency.toUpperCase() }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-3">
                                <div class="text-nowrap text-gray-900 dark:text-gray-200">
                                    <PaymentDetail :detail="payout.detail" :copyable="false" :type="payout.detail_type.code"></PaymentDetail>
                                </div>
                                <div class="text-nowrap text-xs">{{ payout.payment_gateway.name }}</div>
                            </td>
                            <td class="px-6 py-3">
                                <PayoutStatus :status="payout.status" :status_name="payout.status_name"></PayoutStatus>
                            </td>
                            <td class="px-6 py-3">
                                <DateTime class="justify-center" :data="payout.created_at"/>
                            </td>
                            <td class="px-6 py-3 text-right">
                                <Link
                                    v-if="payout.status === 'pending'"
                                    :href="route('trader.payouts.show', payout.id)"
                                    class="px-0 py-0 justify-items-center text-blue-500 hover:text-blue-600 inline-flex items-center hover:underline"
                                >
                                    <svg class="w-[22px] h-[22px]" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.651 7.65a7.131 7.131 0 0 0-12.68 3.15M18.001 4v4h-4m-7.652 8.35a7.13 7.13 0 0 0 12.68-3.15M6 20v-4h4"/>
                                    </svg>
                                </Link>
                                <ShowAction v-else @click.prevent="modalStore.openPayoutModal({payout})"></ShowAction>
                            </td>
                        </tr>
                        </tbody>
                    </table>

                    <table v-if="currentTab === 'payout-offers'" class="w-full text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400">
                        <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                        <tr>
                            <th scope="col" class="px-6 py-3">
                                ID
                            </th>
                            <th scope="col" class="px-6 py-3">
                                Лимиты
                            </th>
                            <th scope="col" class="px-6 py-3 text-nowrap">
                                Метод
                            </th>
                            <th scope="col" class="px-6 py-3">
                                Типы реквизитов
                            </th>
                            <th scope="col" class="px-6 py-3">
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
                        <tr v-for="payoutOffer in payoutOffers.data" class="bg-white border-b last:border-none dark:bg-gray-800 dark:border-gray-700">
                            <th scope="row" class="px-6 py-3 font-medium whitespace-nowrap text-gray-900 dark:text-gray-200">{{ payoutOffer.id }}</th>
                            <td class="px-6 py-3">
                                <div class="text-nowrap text-gray-900 dark:text-gray-200">
                                    Макс: {{ payoutOffer.max_amount }} {{ payoutOffer.currency.toUpperCase() }}
                                </div>
                                <div class="text-nowrap">
                                    Мин: {{ payoutOffer.min_amount }} {{ payoutOffer.currency.toUpperCase() }}
                                </div>
                            </td>
                            <td class="px-6 py-3 text-nowrap">
                                {{ payoutOffer.payment_gateway_name }}
                            </td>
                            <td class="px-6 py-3">
                                        <span
                                            v-for="detailType in payoutOffer.detail_types"
                                            class="inline-flex items-center bg-indigo-100 text-indigo-800 text-xs font-medium me-1 px-1.5 py-0.5 rounded-lg dark:bg-indigo-900 dark:text-indigo-300"
                                        >
                                            {{ detailType.name }}
                                        </span>
                            </td>
                            <td class="px-6 py-3 text-nowrap">
                                {{ payoutOffer.total_payout_amount }} {{ payoutOffer.currency.toUpperCase() }}
                            </td>
                            <td class="px-6 py-3">
                                <IsActiveStatus :is_active="payoutOffer.active"></IsActiveStatus>
                            </td>
                            <td class="px-6 py-3 text-right">
                                <div class="flex justify-center gap-2">
                                    <EditAction :link="route('trader.payout-offers.edit', payoutOffer.id)"></EditAction>
                                </div>
                            </td>
                        </tr>
                        </tbody>
                    </table>
                </div>
            </template>
        </MainTableSection>

        <PayoutModal/>
    </div>
</template>
