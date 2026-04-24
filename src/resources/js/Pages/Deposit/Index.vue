<script setup>
import {Head} from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { usePage } from '@inertiajs/vue3';
import MainTableSection from "@/Wrappers/MainTableSection.vue";
import InvoiceStatus from "@/Components/InvoiceStatus.vue";
import ConfirmModal from "@/Components/Modals/ConfirmModal.vue";
import InputFilter from "@/Components/Filters/Pertials/InputFilter.vue";
import DropdownFilter from "@/Components/Filters/Pertials/DropdownFilter.vue";
import FiltersPanel from "@/Components/Filters/FiltersPanel.vue";
import {ref} from "vue";
import DateTime from "@/Components/DateTime.vue";
import CopyAddress from "@/Components/CopyAddress.vue";

const invoices = usePage().props.invoices;

defineOptions({ layout: AuthenticatedLayout })
</script>

<template>
    <div>
        <Head title="Депозиты средств" />

        <MainTableSection
            title="Депозиты средств"
            :data="invoices"
        >
            <template v-slot:header>
                <FiltersPanel name="deposits">
                    <DropdownFilter
                        name="invoiceStatuses"
                        title="Статусы"
                    />
                    <InputFilter
                        name="id"
                        placeholder="ID депозита"
                    />
                    <InputFilter
                        name="amount"
                        placeholder="Сумма"
                    />
                    <InputFilter
                        name="user"
                        placeholder="Пользователь"
                    />
                </FiltersPanel>
            </template>
            <template v-slot:body>
                <div class="relative overflow-x-auto shadow-md rounded-table ">
                    <table class="w-full text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400">
                        <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                        <tr>
                            <th scope="col" class="px-6 py-3">
                                ID
                            </th>
                            <th scope="col" class="px-6 py-3 text-nowrap">
                                Transaction ID
                            </th>
                            <th scope="col" class="px-6 py-3">
                                Сумма
                            </th>
                            <th scope="col" class="px-6 py-3">
                                Пользователь
                            </th>
                            <th scope="col" class="px-6 py-3">
                                txHash
                            </th>
                            <th scope="col" class="px-6 py-3">
                                Статус
                            </th>
                            <th scope="col" class="px-6 py-3">
                                Дата создания
                            </th>
                        </tr>
                        </thead>
                        <tbody>
                        <tr v-for="invoice in invoices.data" class="bg-white border-b last:border-none dark:bg-gray-800 dark:border-gray-700">
                            <th scope="row" class="px-6 py-3 font-medium whitespace-nowrap text-gray-900 dark:text-gray-200">
                                {{ invoice.id }}
                            </th>
                            <td class="px-6 py-3">
                                {{ invoice.transaction_id }}
                            </td>
                            <td class="px-6 py-3">
                                <div class="text-gray-900 dark:text-gray-200 text-nowrap">{{ invoice.amount }} {{invoice.currency.toUpperCase()}}</div>
                                <div v-show="invoice.balance_type === 'trust'" class="text-xs">
                                    Траст
                                </div>
                                <div v-show="invoice.balance_type === 'merchant'" class="text-xs">
                                    Мерчант
                                </div>
                            </td>
                            <td class="px-6 py-3">
                                {{ invoice.user.email }}
                            </td>
                            <td class="px-6 py-3">
                                <CopyAddress v-if="invoice.tx_hash" :text="invoice.tx_hash"></CopyAddress>
                            </td>
                            <td class="px-6 py-3">
                                <InvoiceStatus :status="invoice.status"></InvoiceStatus>
                            </td>
                            <td class="px-6 py-3 text-nowrap">
                                <DateTime :data="invoice.created_at"></DateTime>
                            </td>
                        </tr>
                        </tbody>
                    </table>
                </div>
            </template>
        </MainTableSection>

        <ConfirmModal/>
    </div>
</template>
