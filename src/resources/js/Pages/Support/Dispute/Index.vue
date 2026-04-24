<script setup>
import {Head, usePage, useForm} from "@inertiajs/vue3";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import PaymentDetail from "@/Components/PaymentDetail.vue";
import DisputeStatus from "@/Components/DisputeStatus.vue";
import {useModalStore} from "@/store/modal.js";
import {useViewStore} from "@/store/view.js";
import DisputeModal from "@/Modals/DisputeModal.vue";
import CancelDisputeModal from "@/Modals/CancelDisputeModal.vue";
import ConfirmModal from "@/Components/Modals/ConfirmModal.vue";
import MainTableSection from "@/Wrappers/MainTableSection.vue";
import DateTime from "@/Components/DateTime.vue";
import ShowAction from "@/Components/Table/ShowAction.vue";
import DisplayUUID from "@/Components/DisplayUUID.vue";
import InputFilter from "@/Components/Filters/Pertials/InputFilter.vue";
import FiltersPanel from "@/Components/Filters/FiltersPanel.vue";
import DropdownFilter from "@/Components/Filters/Pertials/DropdownFilter.vue";

const modalStore = useModalStore();
const viewStore = useViewStore();

const disputes = usePage().props.disputes;
const oldestDisputeCreatedAt = usePage().props.oldestDisputeCreatedAt;

defineOptions({ layout: AuthenticatedLayout })

const handleAcceptDispute = (dispute) => {
    useForm({}).patch(route('support.disputes.accept', dispute.id), {
        preserveScroll: true,
        onSuccess: () => {
            modalStore.closeModal('dispute');
            // Optionally, refresh data or show notification
        },
        onError: (errors) => {
            console.error("Error accepting dispute:", errors);
            // Optionally, show error notification
        }
    });
};

const handleOpenCancelModal = (dispute) => {
    modalStore.openModal('disputeCancel', { dispute: dispute });
};

const handleRollbackDispute = (dispute) => {
    useForm({}).patch(route('support.disputes.rollback', dispute.id), {
        preserveScroll: true,
        onSuccess: () => {
            modalStore.closeModal('dispute');
            // Optionally, refresh data or show notification
        },
        onError: (errors) => {
            console.error("Error rolling back dispute:", errors);
            // Optionally, show error notification
        }
    });
};
</script>

<template>
    <div>
        <Head title="Споры" />

        <MainTableSection
            title="Споры по сделкам"
            :data="disputes"
        >
            <template v-slot:table-filters>
                <div>
                    <FiltersPanel name="orders">
                        <InputFilter
                            name="uuid"
                            placeholder="UUID"
                        />
                        <InputFilter
                            name="externalID"
                            placeholder="Внешний ID"
                        />
                        <InputFilter
                            name="amount"
                            placeholder="Сумма"
                        />
                        <InputFilter
                            name="paymentDetail"
                            placeholder="Реквизит"
                        />
                        <InputFilter
                            name="user"
                            placeholder="Пользователь"
                        />
                        <DropdownFilter
                            name="disputeStatuses"
                            title="Статусы"
                        />
                    </FiltersPanel>
                </div>
            </template>
            <template v-slot:body>
                <div v-if="oldestDisputeCreatedAt" class="flex gap-5">
                    <div class="flex text-base text-gray-500 dark:text-gray-400 mb-3 gap-3">
                        <div>Самый старый:</div>
                        <div>
                            <DateTime :data="oldestDisputeCreatedAt" :plural="true"></DateTime>
                        </div>
                    </div>
                </div>
                <div class="relative overflow-x-auto shadow-md sm:rounded-table ">
                    <table class="w-full text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400">
                        <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                            <tr>
                                <th scope="col" class="px-6 py-3">
                                    ID
                                </th>
                                <th scope="col" class="px-6 py-3 text-nowrap">
                                    Сделка
                                </th>
                                <th scope="col" class="px-6 py-3">
                                    Реквизит
                                </th>
                                <th scope="col" class="px-6 py-3">
                                    Сумма
                                </th>
                                <th scope="col" class="px-6 py-3">
                                    Трейдер
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
                            <tr v-for="dispute in disputes.data" class="bg-white border-b last:border-none dark:bg-gray-800 dark:border-gray-700">
                                <th scope="row" class="px-6 py-3 font-medium whitespace-nowrap text-gray-900 dark:text-gray-200">
                                    {{ dispute.id }}
                                </th>
                                <td class="px-6 py-3">
                                    <DisplayUUID :uuid="dispute.order.uuid"/>
                                </td>
                                <td class="px-6 py-3">
                                    <PaymentDetail
                                        :detail="dispute.payment_detail.detail"
                                        :type="dispute.payment_detail.type"
                                        :copyable="false"
                                        class="text-gray-900 dark:text-gray-200"
                                    ></PaymentDetail>
                                    <div class="text-nowrap text-xs">{{ dispute.payment_detail.name }}</div>
                                </td>
                                <td class="px-6 py-3">
                                    <div class="text-nowrap text-gray-900 dark:text-gray-200">{{ dispute.order.amount }} {{dispute.order.currency.toUpperCase()}}</div>
                                    <div class="text-nowrap text-xs">{{ dispute.order.total_profit }} {{dispute.order.base_currency.toUpperCase()}}</div>
                                </td>
                                <td class="px-6 py-3">
                                    {{ dispute.user.email }}
                                </td>
                                <td class="px-6 py-3">
                                    <DisputeStatus :status="dispute.status"></DisputeStatus>
                                </td>
                                <td class="px-6 py-3">
                                    <DateTime :data="dispute.created_at"></DateTime>
                                </td>
                                <td class="px-6 py-3 text-right">
                                    <ShowAction @click="modalStore.openDisputeModal({dispute})"></ShowAction>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </template>
        </MainTableSection>

        <DisputeModal 
            @accept="handleAcceptDispute"
            @cancel="handleOpenCancelModal" 
            @rollback="handleRollbackDispute"
        />
        <CancelDisputeModal />
        <ConfirmModal />
    </div>
</template>
