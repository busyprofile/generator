<script setup>
import EmptyTable from "@/Components/EmptyTable.vue";
import {router, usePage} from "@inertiajs/vue3";
import {onMounted, ref} from "vue";
import {useViewStore} from "@/store/view.js";
import Pagination from "@/Components/Pagination/Pagination.vue";
import Select from "@/Components/Select.vue";
import DateTime from "@/Components/DateTime.vue";
import CopyAddress from "@/Components/CopyAddress.vue";
import SelectButton from 'primevue/selectbutton';
import Button from 'primevue/button';
import Dropdown from 'primevue/dropdown';
import Tag from 'primevue/tag';

const viewStore = useViewStore();

const user = usePage().props.user;
const invoices = ref(usePage().props.invoices);
const transactions = ref(usePage().props.transactions);
const tabOptions = [
    { label: 'Инвойсы', value: 'invoices' },
    { label: 'Транзакции', value: 'transactions' }
];
const currentTab = ref(usePage().props.currentTab ?? 'invoices');
const filters = ref(usePage().props.filters);
const currentFilters = ref(usePage().props.currentFilters);

router.on('success', (event) => {
    invoices.value = usePage().props.invoices;
    transactions.value = usePage().props.transactions;
})

const openPage = (page) => {
    if (viewStore.isAdminViewMode) {
        router.visit(route('admin.users.wallet.index', user.id), {
            data: {
                page,
                tab: currentTab.value,
                currentFilters: currentFilters.value,
            },
            preserveScroll: true
        })
    } else {
        router.visit(route(route().current()), {
            data: {
                page,
                tab: currentTab.value,
                currentFilters: currentFilters.value,
            },
            preserveScroll: true
        })
    }
}

const currentPage = ref(1);

onMounted(() => {
    let urlParams = new URLSearchParams(window.location.search);
    currentTab.value = urlParams.get('tab') ?? 'invoices';
    currentPage.value = urlParams.get('page') ?? 1;
});

const onTabChange = (val) => {
    currentTab.value = val;
    openPage(1);
};

const openFilters = () => {
    // Здесь можно реализовать открытие модального окна фильтров
};
</script>

<template>
    <h2 class="text-xl font-medium text-gray-900 dark:text-white sm:text-2xl mb-3">История операций</h2>

 <div class="flex justify-between   items-left flex-col md:flex-row gap-2 align-items-left">
    <div class="flex justify-between   items-center">
        <SelectButton
            :options="tabOptions"
            v-model="currentTab"
            optionLabel="label"
            optionValue="value"
            @change="e => onTabChange(e.value)"
            class="w-auto"
        />
    </div>

    <div v-if="filters[currentTab]" class="flex gap-2 md:flex-row flex-col">
        <div v-for="(invoiceFilters, filterKey) in filters[currentTab]" :key="filterKey">
            <Dropdown
                v-model="currentFilters[currentTab][filterKey]"
                :options="Array.isArray(invoiceFilters) ? invoiceFilters : Object.values(invoiceFilters)"
                optionLabel="name"
                optionValue="key"
                :placeholder="(Array.isArray(invoiceFilters) ? invoiceFilters : Object.values(invoiceFilters))[0]?.name || 'Фильтр'"
                class="w-full"
                @change="openPage(1)"
            />
        </div>
    </div>

      </div>

    <div v-if="currentTab === 'invoices'">
        <div class="mx-auto space-y-2">
            <h2
                v-if="!invoices?.data?.length"
                class="mt-7 text-center text-lg font-medium text-gray-900 dark:text-white sm:text-xl mb-4"
            >
                Инвойсы не найдены
            </h2>
            <template v-else>
                <div class="relative overflow-x-auto">
                    <table class="w-full text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400 border-separate border-spacing-y-4 rounded-xl">
                    <tbody>
                    <tr
                        v-for="invoice in invoices.data"
                        class="bg-white dark:bg-gray-800 rounded-table-raw shadow-md"
                    >
                        <th
                            scope="row"
                            class="p-3 font-medium text-gray-900 whitespace-nowrap dark:text-gray-200 rounded-l-table-raw"
                        >
                            <div class="flex items-center">
                                <div class="mr-3">
                                    <span v-if="invoice.status === 'success'" class="inline-flex px-2.5 py-2.5 rounded-2xl bg-green-500 text-green-100 dark:bg-green-800/50 dark:text-green-300">
                                        <svg class="w-5 h-5 dark:text-green-200/80" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                                            <path stroke="currentColor" stroke-linecap="round" stroke-width="2" d="M3 21h18M4 18h16M6 10v8m4-8v8m4-8v8m4-8v8M4 9.5v-.955a1 1 0 0 1 .458-.84l7-4.52a1 1 0 0 1 1.084 0l7 4.52a1 1 0 0 1 .458.84V9.5a.5.5 0 0 1-.5.5h-15a.5.5 0 0 1-.5-.5Z"/>
                                        </svg>
                                    </span>
                                    <span v-if="invoice.status === 'pending'" class="inline-flex px-2.5 py-2.5 rounded-2xl bg-primary/15 text-primary">
                                        <svg class="w-5 h-5 dark:text-red-200/80" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                                            <path stroke="currentColor" stroke-linecap="round" stroke-width="2" d="M3 21h18M4 18h16M6 10v8m4-8v8m4-8v8m4-8v8M4 9.5v-.955a1 1 0 0 1 .458-.84l7-4.52a1 1 0 0 1 1.084 0l7 4.52a1 1 0 0 1 .458.84V9.5a.5.5 0 0 1-.5.5h-15a.5.5 0 0 1-.5-.5Z"/>
                                        </svg>
                                    </span>
                                    <span v-if="invoice.status === 'fail'" class="inline-flex px-2.5 py-2.5 rounded-2xl bg-red-500 text-red-100 dark:bg-red-800/50 dark:text-red-300">
                                        <svg class="w-5 h-5 dark:text-red-200/80" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                                            <path stroke="currentColor" stroke-linecap="round" stroke-width="2" d="M3 21h18M4 18h16M6 10v8m4-8v8m4-8v8m4-8v8M4 9.5v-.955a1 1 0 0 1 .458-.84l7-4.52a1 1 0 0 1 1.084 0l7 4.52a1 1 0 0 1 .458.84V9.5a.5.5 0 0 1-.5.5h-15a.5.5 0 0 1-.5-.5Z"/>
                                        </svg>
                                    </span>
                                </div>
                                <div class="text-gray-900 dark:text-gray-400">#{{ invoice.id }}</div>
                            </div>
                        </th>
                        <td class="p-3 text-gray-900">
                            <div class="text-nowrap dark:text-gray-400 text-center">
                                <template v-if="invoice.type === 'deposit'">Пополнение</template>
                                <template v-if="invoice.type === 'withdrawal'">Вывод</template>
                            </div>
                        </td>
                        <td v-show="viewStore.isAdminViewMode" class="p-3 text-gray-900">
                            <div class="text-nowrap dark:text-gray-400 text-center">
                                <template v-if="invoice.balance_type === 'trust'">Траст</template>
                                <template v-if="invoice.balance_type === 'merchant'">Мерчант</template>
                            </div>
                        </td>
                        <td class="p-3 text-gray-900">
                            <div class="text-nowrap dark:text-gray-400 text-center">
                                <template v-if="invoice.type === 'deposit'">+</template>
                                <template v-if="invoice.type === 'withdrawal'">-</template>
                                {{ invoice.amount }} {{ invoice.currency.toUpperCase() }}
                            </div>
                        </td>
                        <td class="p-3">
                            <div class="text-nowrap dark:text-gray-400 text-center">
                                {{ invoice.address }}
                            </div>
                        </td>
                        <td class="p-3">
                            <div class="flex justify-center">
                                <DateTime class="" :data="invoice.created_at"/>
                            </div>
                        </td>
                        <td class="p-3 rounded-r-table-raw">
                            <div class="flex justify-end">
                                <Tag v-if="invoice.status === 'success'" value="Успешно" severity="success" class="mr-2"></Tag>
                                <Tag v-if="invoice.status === 'pending'" value="Ожидание" severity="warn" class="mr-2"></Tag>
                                <Tag v-if="invoice.status === 'fail'" value="Ошибка" severity="danger" class="mr-2"></Tag>
                            </div>
                        </td>
                    </tr>
                    </tbody>
                </table>
                </div>
                <Pagination
                    v-model="invoices.meta.current_page"
                    :total-items="invoices.meta.total"
                    previous-label="Назад" next-label="Вперед"
                    @page-changed="openPage"
                    :per-page="invoices.meta.per_page"
                ></Pagination>
            </template>
        </div>
    </div>

    <div v-if="currentTab === 'transactions'">
        <div class="mx-auto space-y-2">
            <h2
                v-if="!transactions?.data?.length"
                class="mt-7 text-center text-lg font-medium text-gray-900 dark:text-white sm:text-xl mb-4"
            >
                Инвойсы не найдены
            </h2>
            <template v-else>
                <div class="relative overflow-x-auto">
                    <table class="w-full text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400 border-separate border-spacing-y-4 rounded-xl">
                    <tbody>
                    <tr
                        v-for="transaction in transactions.data"
                        class="bg-white dark:bg-gray-800 rounded-table-raw shadow-md"
                    >
                        <th
                            scope="row"
                            class="p-3 font-medium text-gray-900 whitespace-nowrap dark:text-gray-200 rounded-l-table-raw"
                        >
                            <div class="flex items-center">
                                <div class="mr-3">
                                    <span v-if="transaction.direction === 'in'" class="inline-flex px-2.5 py-2.5 rounded-2xl bg-green-500 text-green-100 dark:bg-green-800/50 dark:text-green-300">
                                        <svg class="w-5 h-5 dark:text-green-200/80" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                                            <path stroke="currentColor" stroke-linecap="round" stroke-width="2" d="M3 21h18M4 18h16M6 10v8m4-8v8m4-8v8m4-8v8M4 9.5v-.955a1 1 0 0 1 .458-.84l7-4.52a1 1 0 0 1 1.084 0l7 4.52a1 1 0 0 1 .458.84V9.5a.5.5 0 0 1-.5.5h-15a.5.5 0 0 1-.5-.5Z"/>
                                        </svg>
                                    </span>
                                    <span v-if="transaction.direction === 'out'" class="inline-flex px-2.5 py-2.5 rounded-2xl bg-red-500 text-red-100 dark:bg-red-800/50 dark:text-red-300">
                                        <svg class="w-5 h-5 dark:text-red-200/80" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                                            <path stroke="currentColor" stroke-linecap="round" stroke-width="2" d="M3 21h18M4 18h16M6 10v8m4-8v8m4-8v8m4-8v8M4 9.5v-.955a1 1 0 0 1 .458-.84l7-4.52a1 1 0 0 1 1.084 0l7 4.52a1 1 0 0 1 .458.84V9.5a.5.5 0 0 1-.5.5h-15a.5.5 0 0 1-.5-.5Z"/>
                                        </svg>
                                    </span>
                                </div>
                                <div class="text-gray-900 dark:text-gray-400">#{{ transaction.id }}</div>
                            </div>
                        </th>
                        <td class="p-3">
                            <div class="text-nowrap text-gray-900 dark:text-gray-400 text-center">
                                <template v-if="transaction.direction === 'in'">+</template>
                                <template v-if="transaction.direction === 'out'">-</template>
                                {{ transaction.amount }} {{ transaction.currency.toUpperCase() }}
                            </div>
                        </td>
                        <td class="p-3">
                            <div class="flex justify-center gap-2 text-gray-900 dark:text-gray-400">
                                <p class="font-medium">{{ transaction.type_name }}</p>
                            </div>
                        </td>
                        <td class="p-3 text-nowrap">
                            <div class="flex justify-center">
                                <DateTime class="" :data="transaction.created_at"/>
                            </div>
                        </td>
                        <td class="p-3 rounded-r-table-raw">
                            <div class="flex justify-end">
                                <Tag v-if="transaction.direction === 'in'"  value="Зачисление" severity="success" class="mr-2"></Tag> 
                                <Tag v-if="transaction.direction === 'out'" value="Снятие" severity="danger" class="mr-2"></Tag>

 
                            </div>
                        </td>
                    </tr>
                    </tbody>
                </table>
                </div>
                <Pagination
                    v-model="transactions.meta.current_page"
                    :total-items="transactions.meta.total"
                    previous-label="Назад" next-label="Вперед"
                    @page-changed="openPage"
                    :per-page="transactions.meta.per_page"
                ></Pagination>
            </template>
        </div>
    </div>
</template>

<style scoped>

</style>
