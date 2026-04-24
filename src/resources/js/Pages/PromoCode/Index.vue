<script setup>
import {Head, router, usePage} from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import MainTableSection from "@/Wrappers/MainTableSection.vue";
import {ref} from "vue";
import TableActionsDropdown from "@/Components/Table/TableActionsDropdown.vue";
import TableAction from "@/Components/Table/TableAction.vue";
import ConfirmModal from "@/Components/Modals/ConfirmModal.vue";
import {useModalStore} from "@/store/modal.js";
import IsActiveStatus from "@/Components/IsActiveStatus.vue";
import DateTime from "@/Components/DateTime.vue";
import AddMobileIcon from "@/Components/AddMobileIcon.vue";
import {useViewStore} from "@/store/view.js";
import InputFilter from "@/Components/Filters/Pertials/InputFilter.vue";
import FiltersPanel from "@/Components/Filters/FiltersPanel.vue";
import FilterCheckbox from "@/Components/Filters/Pertials/FilterCheckbox.vue";

const modalStore = useModalStore();
const viewStore = useViewStore();
const promoCodes = ref(usePage().props.promoCodes);

// Определяем префикс для маршрутов
const routePrefix = viewStore.isAdminViewMode ? 'admin' : 'leader';

router.on('success', (event) => {
    promoCodes.value = usePage().props.promoCodes;
})

const confirmDeletePromoCode = (promoCode) => {
    modalStore.openConfirmModal({
        title: 'Вы уверены что хотите удалить промокод "' + promoCode.code + '"?',
        body: 'Это действие невозможно отменить.',
        confirm_button_name: 'Удалить',
        confirm: () => {
            router.delete(route(routePrefix + '.promo-codes.destroy', promoCode.id), {
                preserveScroll: true
            });
        }
    });
};

defineOptions({ layout: AuthenticatedLayout })
</script>

<template>
    <div>
        <Head title="Промокоды" />

        <MainTableSection
            title="Промокоды"
            :data="promoCodes"
        >
            <template v-slot:button v-if="!viewStore.isAdminViewMode">
                <button
                    @click="router.visit(route(routePrefix + '.promo-codes.create'))"
                    type="button"
                    class="hidden md:block text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:ring-blue-300 font-medium rounded-xl  text-sm px-5 py-2.5 dark:bg-blue-600 dark:hover:bg-blue-700 focus:outline-none dark:focus:ring-blue-800"
                >
                    Создать промокод
                </button>
                <AddMobileIcon
                    @click="router.visit(route(routePrefix + '.promo-codes.create'))"
                />
            </template>

            <template v-slot:table-filters>
                <FiltersPanel name="promo-codes">
                    <InputFilter
                        name="search"
                        placeholder="Код"
                    />
                    <InputFilter
                        v-if="viewStore.isAdminViewMode"
                        name="user"
                        placeholder="Тимлидер"
                    />
                    <FilterCheckbox
                        name="active"
                        title="Активные"
                    />
                </FiltersPanel>
            </template>

            <template v-slot:body>
                <div class="relative overflow-x-auto shadow-md rounded-table">
                    <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                        <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                        <tr>
                            <th scope="col" class="px-6 py-3">
                                Код
                            </th>
                            <th scope="col" class="px-6 py-3">
                                Макс. использований
                            </th>
                            <th scope="col" class="px-6 py-3">
                                Использовано
                            </th>
                            <th scope="col" v-if="viewStore.isAdminViewMode" class="px-6 py-3">
                                Владелец
                            </th>
                            <th scope="col" class="px-6 py-3">
                                Статус
                            </th>
                            <th scope="col" class="px-6 py-3">
                                Дата создания
                            </th>
                            <th scope="col" class="px-6 py-3 text-right">
                                <span class="sr-only">Действия</span>
                            </th>
                        </tr>
                        </thead>
                        <tbody>
                        <tr v-for="promoCode in promoCodes.data" :key="promoCode.id" class="bg-white border-b dark:bg-gray-800 dark:border-gray-700">
                            <th scope="row" class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap dark:text-white">
                                {{ promoCode.code }}
                            </th>
                            <td class="px-6 py-4">
                                {{ promoCode.max_uses }}
                            </td>
                            <td class="px-6 py-4">
                                {{ promoCode.used_count }}
                            </td>
                            <td v-if="viewStore.isAdminViewMode" class="px-6 py-4">
                                {{ promoCode.team_leader?.name || 'Не указан' }}
                            </td>
                            <td class="px-6 py-4">
                                <IsActiveStatus :is_active="promoCode.is_active" />
                            </td>
                            <td class="px-6 py-4">
                                <DateTime :data="promoCode.created_at" />
                            </td>
                            <td class="px-6 py-4 text-right relative">
                                <TableActionsDropdown>
                                    <TableAction @click="router.visit(route(routePrefix + '.promo-codes.edit', promoCode.id))">
                                        Редактировать
                                    </TableAction>
                                    <TableAction v-if="!viewStore.isAdminViewMode" @click="confirmDeletePromoCode(promoCode)">
                                        Удалить
                                    </TableAction>
                                </TableActionsDropdown>
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
