<script setup>
import {Head, router, useForm} from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { usePage } from '@inertiajs/vue3';
import PaymentDetail from "@/Components/PaymentDetail.vue";
import PaymentDetailLimit from "@/Components/PaymentDetailLimit.vue";
import MainTableSection from "@/Wrappers/MainTableSection.vue";
import {useViewStore} from "@/store/view.js";
import AddMobileIcon from "@/Components/AddMobileIcon.vue";
import {computed, onMounted, ref, watch, onUnmounted} from "vue";
import InputFilter from "@/Components/Filters/Pertials/InputFilter.vue";
import FiltersPanel from "@/Components/Filters/FiltersPanel.vue";
import FilterCheckbox from "@/Components/Filters/Pertials/FilterCheckbox.vue";
import GatewayLogo from "@/Components/GatewayLogo.vue";
import TableActionsDropdown from "@/Components/Table/TableActionsDropdown.vue";
import TableAction from "@/Components/Table/TableAction.vue";
import ConfirmModal from "@/Components/Modals/ConfirmModal.vue";
import {useModalStore} from "@/store/modal.js";
import {useTableFiltersStore} from "@/store/tableFilters.js";
import DropdownFilter from "@/Components/Filters/Pertials/DropdownFilter.vue";
import PaymentDetailCard from '@/Components/PaymentDetailCard.vue';
import EmptyTable from '@/Components/EmptyTable.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import Card from 'primevue/card';
import SelectButton from 'primevue/selectbutton';
import InputSwitch from 'primevue/inputswitch';

const modalStore = useModalStore();
const viewStore = useViewStore();
const paymentDetails = ref(usePage().props.paymentDetails)
const detailActiveToggleForm = useForm({});
const currentTab = ref('active');
const tableFiltersStore = useTableFiltersStore();
const loadingDetailId = ref(null);
const archivingDetailId = ref(null);

const screenWidth = ref(window.innerWidth);

const updateScreenWidth = () => {
    screenWidth.value = window.innerWidth;
};

const selectButtonSize = computed(() => {
    return screenWidth.value < 768 ? 'small' : null;
});

const displayShortDetail = ref(getCookieValue('displayShortDetail', true));

function getCookieValue(name, defaultValue) {
    const currentRoute = route().current();
    const cookieName = `${name}_${currentRoute}`;
    const match = document.cookie.match(new RegExp('(^| )' + cookieName + '=([^;]+)'));
    return match ? match[2] === 'true' : defaultValue;
}

function updateDisplayShortDetailCookie() {
    const currentRoute = route().current();
    const cookieName = `displayShortDetail_${currentRoute}`;
    document.cookie = `${cookieName}=${displayShortDetail.value}; path=/; max-age=31536000`; // 1 год
}

// Следим за изменениями и обновляем cookie
watch(displayShortDetail, () => {
    updateDisplayShortDetailCookie();
});

const currentUser = usePage().props.auth?.user;

// Определяем, является ли текущий пользователь VIP
const isVipUser = computed(() => {
    return currentUser?.is_vip === true || currentUser?.is_vip === 1;
});

const toggleActive = (detail_id) => {
    loadingDetailId.value = detail_id;
    detailActiveToggleForm.patch(route('payment-details.toggle-active', detail_id), {
        preserveScroll: true,
        onSuccess: (result) => {
            paymentDetails.value = result.props.paymentDetails;
        },
        onError: () => {
            // Handle error if needed
        },
        onFinish: () => {
            loadingDetailId.value = null;
        },
    });
};

router.on('success', (event) => {
    // Обновляем данные рекивизитов из ответа сервера
    paymentDetails.value = usePage().props.paymentDetails;
    // Сбрасываем флаг загрузки архивации
    archivingDetailId.value = null;
    
    // Закрываем модальное окно, если оно все еще открыто
    if (modalStore.confirmModal.showed) {
        modalStore.closeModal('confirm');
    }
})

const confirmArchiveDetail = (detail) => {
    modalStore.openConfirmModal({
        title: 'Вы уверены что хотите архивировать реквизит #' + detail.id + '?',
        body: 'Действие можно отменить.',
        confirm_button_name: 'Архивировать',
        confirm: () => {
            archivingDetailId.value = detail.id;
            router.post(route('payment-details.archive', detail.id), {}, {
                preserveScroll: true,
                onSuccess: () => {
                    modalStore.closeModal('confirm');
                },
                onFinish: () => {
                    // Если запрос завершился, сбрасываем состояние загрузки
                    if (archivingDetailId.value === detail.id) {
                        archivingDetailId.value = null;
                    }
                }
            });
        }
    });
};

const confirmUnarchiveDetail = (detail) => {
    modalStore.openConfirmModal({
        title: 'Вы уверены что хотите вернуть реквизит из архива #' + detail.id + '?',
        body: 'Действие можно отменить.',
        confirm_button_name: 'Вернуть',
        confirm: () => {
            archivingDetailId.value = detail.id;
            router.delete(route('payment-details.unarchive', detail.id), {}, {
                preserveScroll: true,
                onSuccess: () => {
                    modalStore.closeModal('confirm');
                },
                onFinish: () => {
                    // Если запрос завершился, сбрасываем состояние загрузки
                    if (archivingDetailId.value === detail.id) {
                        archivingDetailId.value = null;
                    }
                }
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

onMounted(() => {
    window.addEventListener('resize', updateScreenWidth);

    if (tableFiltersStore.getTab === '') {
        tableFiltersStore.setTab('active');
    }
    currentTab.value = tableFiltersStore.getTab
})

onUnmounted(() => {
    window.removeEventListener('resize', updateScreenWidth);
});

const props = defineProps({
    paymentDetails: {
        type: Object,
        default: () => ({ data: [] }),
    },
    currentTab: {
        type: String,
        default: 'active',
    }
});

const tabOptions = ref([
    {label: 'Активные', value: 'active', icon: 'pi pi-book'},
    {label: 'Архив', value: 'archived', icon: 'pi pi-inbox'}
]);

defineOptions({ layout: AuthenticatedLayout })
</script>

<template>
    <div>
        <Head title="Реквизиты" />

        <MainTableSection
            title="Реквизиты"
            :data="paymentDetails"
        >
            <template v-slot:button>
                <div class="flex ">
                    <!-- <button
                        @click="router.visit(route(viewStore.adminPrefix + 'payment-details.create'))"
                        type="button"
                        class="hidden md:block text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:ring-blue-300 font-medium rounded-xl  text-sm px-5 py-2.5 dark:bg-blue-600 dark:hover:bg-blue-700 focus:outline-none dark:focus:ring-blue-800"
                    >
                        Создать реквизиты
                    </button> -->
                    <AddMobileIcon
                        @click="router.visit(route(viewStore.adminPrefix + 'payment-details.create'))"
                    />
                    <!-- <div class="flex items-center ml-auto">
                        <InputSwitch inputId="shortDetailToggle" v-model="displayShortDetail" />
                    </div> -->
                </div>
            </template>
            <template v-slot:header>
 
<div class="flex justify-between items-center mb-4">
                <div class="tab-buttons flex">
                    <SelectButton
                        v-model="currentTab"
                        :options="tabOptions"
                        optionLabel="label"
                        optionValue="value"
                        @change="openPage($event.value)"
                        aria-labelledby="basic"
                        :size="selectButtonSize"
                    >
                         <template #option="slotProps">
                            <div class="flex items-center ">
                                <i :class="['pi', slotProps.option.icon, 'mr-2']"></i>
                                <span>{{ slotProps.option.label }}</span>
                            </div>
                         </template>
                    </SelectButton>
                </div>

                    <FiltersPanel name="payment-details">
                    <InputFilter
                        name="id"
                        placeholder="ID реквизита"
                    />
                    <InputFilter
                        name="name"
                        placeholder="Название"
                    />
                    <DropdownFilter
                        name="detailTypes"
                        title="Тип реквизита"
                    />
                    <InputFilter
                        name="paymentGateway"
                        placeholder="Платежный метод"
                    />
                    <InputFilter
                        name="paymentDetail"
                        placeholder="Реквизит"
                    />
                    <InputFilter
                        v-if="viewStore.isAdminViewMode"
                        name="user"
                        placeholder="Пользователь"
                    />
                    <FilterCheckbox
                        name="active"
                        title="Включенные"
                    />
                    <FilterCheckbox
                        v-if="viewStore.isAdminViewMode"
                        name="multipliedDetails"
                        title="Размноженные"
                    />
                    <FilterCheckbox
                        v-if="viewStore.isAdminViewMode"
                        name="online"
                        title="Онлайн"
                    />
                </FiltersPanel>
</div>

            </template>



            
            <template v-slot:body>
                <div v-if="paymentDetails.data.length > 0" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                    <PaymentDetailCard
                        v-for="payment_detail in paymentDetails.data"
                        :key="payment_detail.id"
                        :detail="payment_detail"
                        :displayShortDetail="displayShortDetail"
                        :isAdminViewMode="viewStore.isAdminViewMode"
                        :isVipUser="isVipUser"
                        :currentTab="currentTab"
                        :isTogglingActive="loadingDetailId === payment_detail.id"
                        :isArchiving="archivingDetailId === payment_detail.id"
                        @toggle-active="toggleActive"
                        @edit="router.visit(route(viewStore.adminPrefix + 'payment-details.edit', $event.id))"
                        @archive="confirmArchiveDetail"
                        @unarchive="confirmUnarchiveDetail"
                    />
                </div>
                <div v-else>
                    <EmptyTable/>
                </div>
            </template>
        </MainTableSection>

        <ConfirmModal/>
    </div>
</template>

<style>
/* Принудительное переопределение стилей иконок PrimeVue для гарантии их отображения */
.pi {
    font-family: 'primeicons', sans-serif !important;
}
</style>
