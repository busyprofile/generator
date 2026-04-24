<script setup>
import {Head, usePage} from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import MainTableSection from "@/Wrappers/MainTableSection.vue";
import InputFilter from "@/Components/Filters/Pertials/InputFilter.vue";
import FiltersPanel from "@/Components/Filters/FiltersPanel.vue";
import {computed, ref} from "vue";

const users = ref(usePage().props.users);
const totals = ref(usePage().props.totals);

const formatNumber = (num) => { //TODO move to utils
    // Округляем до двух знаков после запятой, если есть дробная часть
    const roundedNum = Math.round(num * 100) / 100;

    // Форматируем число с разделителями тысяч
    return roundedNum.toLocaleString('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    });
}

const totalsFormated = computed(() => {
    const canSee = usePage().props.auth.can_see_finances;
    return {
        trust_balance: canSee ? formatNumber(totals.value.trust_balance) : '****',
        merchant_balance: canSee ? formatNumber(totals.value.merchant_balance) : '****',
        total_balance: canSee ? formatNumber(totals.value.total_balance) : '****',
        trust_deposits: canSee ? formatNumber(totals.value.trust_deposits) : '****',
        trust_withdrawals: canSee ? formatNumber(totals.value.trust_withdrawals) : '****',
        merchant_deposits: canSee ? formatNumber(totals.value.merchant_deposits) : '****',
        merchant_withdrawals: canSee ? formatNumber(totals.value.merchant_withdrawals) : '****',
        payment_for_orders: canSee ? formatNumber(totals.value.payment_for_orders) : '****',
    };
});

defineOptions({ layout: AuthenticatedLayout })
</script>

<template>
    <div>
        <Head title="Учет средств пользователей" />

        <MainTableSection
            title="Учет средств пользователей"
            :data="users"
        >
            <template v-slot:header>
                <FiltersPanel name="user-balances">
                    <InputFilter
                        name="user"
                        placeholder="Поиск (почта или имя)"
                        class="w-64"
                    />
                </FiltersPanel>
            </template>
            <template v-slot:body>
                <div class="mb-4 p-4 bg-white dark:bg-gray-800 rounded-xl shadow-md">
                    <h3 class="text-lg font-semibold mb-2 text-gray-900 dark:text-white">Итоговые суммы</h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                        <div class="p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                            <div class="text-sm text-gray-500 dark:text-gray-400">Траст баланс</div>
                            <div class="text-xl font-bold text-gray-900 dark:text-gray-200">
                                {{ totalsFormated.trust_balance }} $
                            </div>
                        </div>
                        <div class="p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                            <div class="text-sm text-gray-500 dark:text-gray-400">Мерчант баланс</div>
                            <div class="text-xl font-bold text-gray-900 dark:text-gray-200">
                                {{ totalsFormated.merchant_balance }} $
                            </div>
                        </div>
                        <div class="p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                            <div class="text-sm text-gray-500 dark:text-gray-400">Общий баланс</div>
                            <div class="text-xl font-bold text-gray-900 dark:text-gray-200">
                                {{ totalsFormated.total_balance }} $
                            </div>
                        </div>
                    </div>

                    <h3 class="text-lg font-semibold mb-2 text-gray-900 dark:text-white">Итоговые суммы операций</h3>
                    <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
                        <div class="p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                            <div class="text-sm text-gray-500 dark:text-gray-400">Зачисления на траст</div>
                            <div class="text-xl font-bold text-green-600 dark:text-green-500">
                                {{ totalsFormated.trust_deposits }} $
                            </div>
                        </div>
                        <div class="p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                            <div class="text-sm text-gray-500 dark:text-gray-400">Выводы с траста</div>
                            <div class="text-xl font-bold text-red-600 dark:text-red-500">
                                {{ totalsFormated.trust_withdrawals }} $
                            </div>
                        </div>
                        <div class="p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                            <div class="text-sm text-gray-500 dark:text-gray-400">Зачисления на мерчант</div>
                            <div class="text-xl font-bold text-green-600 dark:text-green-500">
                                {{ totalsFormated.merchant_deposits }} $
                            </div>
                        </div>
                        <div class="p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                            <div class="text-sm text-gray-500 dark:text-gray-400">Выводы с мерчанта</div>
                            <div class="text-xl font-bold text-red-600 dark:text-red-500">
                                {{ totalsFormated.merchant_withdrawals }} $
                            </div>
                        </div>
                        <div class="p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                            <div class="text-sm text-gray-500 dark:text-gray-400">Оплата сделок</div>
                            <div class="text-xl font-bold text-red-600 dark:text-red-500">
                                {{ totalsFormated.payment_for_orders }} $
                            </div>
                        </div>
                    </div>
                </div>

                <div class="relative overflow-x-auto shadow-md rounded-table">
                    <table class="w-full text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400">
                        <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                        <tr>
                            <th scope="col" class="px-6 py-3">
                                ID
                            </th>
                            <th scope="col" class="px-6 py-3">
                                Пользователь
                            </th>
                            <th scope="col" class="px-6 py-3">
                                Роль
                            </th>
                            <th scope="col" class="px-6 py-3">
                                Cделки
                            </th>
                            <th scope="col" class="px-6 py-3">
                                Траст
                            </th>
                            <th scope="col" class="px-6 py-3">
                                Зачисления
                            </th>
                            <th scope="col" class="px-6 py-3">
                                Выводы
                            </th>
                            <th scope="col" class="px-6 py-3">
                                Мерчант
                            </th>
                            <th scope="col" class="px-6 py-3">
                                Зачисления
                            </th>
                            <th scope="col" class="px-6 py-3">
                                Выводы
                            </th>
                        </tr>
                        </thead>
                        <tbody>
                        <tr v-for="user in users.data" class="bg-white border-b last:border-none dark:bg-gray-800 dark:border-gray-700">
                            <th scope="row" class="px-6 py-3 font-medium whitespace-nowrap text-gray-900 dark:text-gray-200">
                                {{ user.id }}
                            </th>
                            <td class="px-6 py-3 text-nowrap">
                                <div class="inline-flex items-center gap-2">
                                    <div>
                                        <div class="text-nowrap text-gray-900 dark:text-gray-200">
                                            {{ user.email }}
                                        </div>
                                        <div class="text-nowrap text-xs">
                                            {{ user.name }}
                                        </div>
                                    </div>
                                    <span
                                        v-if="user.banned_at"
                                    >
                                        <svg class="w-4 h-4 text-red-600" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                                            <path fill-rule="evenodd" d="M2 12C2 6.477 6.477 2 12 2s10 4.477 10 10-4.477 10-10 10S2 17.523 2 12Zm7.707-3.707a1 1 0 0 0-1.414 1.414L10.586 12l-2.293 2.293a1 1 0 1 0 1.414 1.414L12 13.414l2.293 2.293a1 1 0 0 0 1.414-1.414L13.414 12l2.293-2.293a1 1 0 0 0-1.414-1.414L12 10.586 9.707 8.293Z" clip-rule="evenodd"/>
                                        </svg>
                                    </span>
                                </div>
                            </td>
                            <td class="px-6 py-3 text-nowrap">
                                {{ user.role.name }}
                            </td>
                            <td class="px-6 py-3 text-nowrap font-medium text-red-600 dark:text-red-500">
                                -{{ $page.props.auth.can_see_finances ? user.wallet.payment_for_orders : '****' }} $
                            </td>
                            <td class="px-6 py-3 text-nowrap font-medium">
                                {{ $page.props.auth.can_see_finances ? user.wallet.trust_balance : '****' }} $
                            </td>
                            <td class="px-6 py-3 text-nowrap font-medium text-green-600 dark:text-green-500">
                                +{{ $page.props.auth.can_see_finances ? user.wallet.trust_deposits : '****' }} $
                            </td>
                            <td class="px-6 py-3 text-nowrap font-medium text-red-600 dark:text-red-500">
                                -{{ $page.props.auth.can_see_finances ? user.wallet.trust_withdrawals : '****' }} $
                            </td>
                            <td class="px-6 py-3 text-nowrap font-medium">
                                {{ $page.props.auth.can_see_finances ? user.wallet.merchant_balance : '****' }} $
                            </td>
                            <td class="px-6 py-3 text-nowrap font-medium text-green-600 dark:text-green-500">
                                +{{ $page.props.auth.can_see_finances ? user.wallet.merchant_deposits : '****' }} $
                            </td>
                            <td class="px-6 py-3 text-nowrap font-medium text-red-600 dark:text-red-500">
                                -{{ $page.props.auth.can_see_finances ? user.wallet.merchant_withdrawals : '****' }} $
                            </td>
                        </tr>
                        </tbody>
                    </table>
                </div>
            </template>
        </MainTableSection>
    </div>
</template>
