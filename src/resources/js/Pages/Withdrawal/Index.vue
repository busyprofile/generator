<script setup>
import {Head, router, useForm} from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { usePage } from '@inertiajs/vue3';
import MainTableSection from "@/Wrappers/MainTableSection.vue";
import InvoiceStatus from "@/Components/InvoiceStatus.vue";
import SuccessAction from "@/Components/Table/SuccessAction.vue";
import FailAction from "@/Components/Table/FailAction.vue";
import {useModalStore} from "@/store/modal.js";
import ConfirmModal from "@/Components/Modals/ConfirmModal.vue";
import CopyAddress from "@/Components/CopyAddress.vue";
import InputFilter from "@/Components/Filters/Pertials/InputFilter.vue";
import DropdownFilter from "@/Components/Filters/Pertials/DropdownFilter.vue";
import FiltersPanel from "@/Components/Filters/FiltersPanel.vue";
import {ref} from "vue";
import DateTime from "@/Components/DateTime.vue";

const modalStore = useModalStore();

const invoices = ref(usePage().props.invoices);

const confirmSuccessWithdrawal = (invoice) => {
    modalStore.openConfirmModal({
        title: 'Вы уверены что хотите завершить заявку как успешную?',
        confirm_button_name: 'Подтвердить',
        confirm: () => {
            useForm({}).patch(route('admin.withdrawals.success', invoice.id), {
                preserveScroll: true,
            });
        }
    });
};

const confirmFailParser = (invoice) => {
    modalStore.openConfirmModal({
        title: 'Вы уверены что хотите отклонить заявку?',
        confirm_button_name: 'Отклонить',
        confirm: () => {
            useForm({}).patch(route('admin.withdrawals.fail', invoice.id), {
                preserveScroll: true,
            });
        }
    });
};

router.on('success', () => {
    invoices.value = usePage().props.invoices;
})

defineOptions({ layout: AuthenticatedLayout })
</script>

<template>
    <div>
        <Head title="Заявки на вывод средств" />

        <MainTableSection
            title="Заявки на вывод средств"
            :data="invoices"
        >
            <template v-slot:header>
                <FiltersPanel name="withdrawals">
                    <DropdownFilter
                        name="invoiceStatuses"
                        title="Статусы"
                    />
                    <InputFilter
                        name="id"
                        placeholder="ID вывода"
                    />
                    <InputFilter
                        name="amount"
                        placeholder="Сумма"
                    />
                    <InputFilter
                        name="user"
                        placeholder="Пользователь"
                    />
                    <InputFilter
                        name="address"
                        placeholder="Адрес"
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
                                External ID
                            </th>
                            <th scope="col" class="px-6 py-3">
                                Сумма
                            </th>
                            <th scope="col" class="px-6 py-3">
                                Пользователь
                            </th>
                            <th scope="col" class="px-6 py-3">
                                Адрес
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
                            <th scope="col" class="px-6 py-3 flex justify-center">
                                <span class="sr-only">Действия</span>
                            </th>
                        </tr>
                        </thead>
                        <tbody>
                        <tr v-for="invoice in invoices.data" class="bg-white border-b last:border-none dark:bg-gray-800 dark:border-gray-700">
                            <th scope="row" class="px-6 py-3 font-medium whitespace-nowrap text-gray-900 dark:text-gray-200">
                                {{ invoice.id }}
                            </th>
                            <td class="px-6 py-3">
                                {{ invoice.external_id }}
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
                                <div class="flex gap-2">
                                    <CopyAddress v-if="invoice.address" :text="invoice.address"></CopyAddress>
                                    <div class="text-blue-500">{{ invoice.network?.toUpperCase() }}</div>
                                </div>
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
                            <td class="px-6 py-3 text-nowrap text-right">
                                <template v-if="invoice.status === 'pending'">
                                    <SuccessAction @click.prevent="confirmSuccessWithdrawal(invoice)"/>
                                    <FailAction class="ml-3" @click.prevent="confirmFailParser(invoice)"/>
                                </template>
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
