<script setup>
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { ref, computed, watch, onUnmounted } from 'vue';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import Dialog from 'primevue/dialog';
import Dropdown from 'primevue/dropdown';
import InputNumber from 'primevue/inputnumber';
import Sidebar from 'primevue/sidebar';
import Paginator from 'primevue/paginator';
import Tag from 'primevue/tag';
import Card from 'primevue/card';
import Menu from 'primevue/menu';
import Chip from 'primevue/chip';
import GatewayLogo from '@/Components/GatewayLogo.vue';
import OrderStatus from '@/Components/OrderStatus.vue';
import DateTime from '@/Components/DateTime.vue';
import DisplayUUID from '@/Components/DisplayUUID.vue';
import OrderModal from '@/Modals/OrderModal.vue';
import { useModalStore } from '@/store/modal.js';
import { useClipboard } from '@vueuse/core';

const props = defineProps({
    paymentDetails: { type: Array, default: () => [] },
    pagination: { type: Object, default: () => ({ total: 0, per_page: 15, current_page: 1, last_page: 1 }) },
    orders: { type: Array, default: () => [] },
    paymentGateways: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({ filter_name: '', filter_gateway_id: null }) },
});

const modalStore = useModalStore();
const { copy, copied } = useClipboard();

const openOrderModal = (order) => {
    modalStore.openOrderModal({ order_id: order.id });
};

const formatDetailShort = (detail, type) => {
    if (type && detail) {
        if (type.includes('card') && detail.length > 10) {
            return `**** ${detail.substring(detail.length - 4)}`;
        }
    }
    return detail || '—';
};

const paymentDetailsList = ref(props.paymentDetails);
const ordersList = ref(props.orders);
const paginationData = ref(props.pagination);

router.on('success', () => {
    paymentDetailsList.value = usePage().props.paymentDetails ?? [];
    ordersList.value = usePage().props.orders ?? [];
    paginationData.value = usePage().props.pagination ?? { total: 0, per_page: 15, current_page: 1, last_page: 1 };
});

const selectedDetail = ref(null);
const showAddModal = ref(false);
const showEditModal = ref(false);
const editingDetail = ref(null);
const mobileView = ref('requisites');
const confirmingOrderId = ref(null);
const toggleLoadingId = ref(null);
const archivingId = ref(null);

// Ссылки на меню трёх точек (по id реквизита)
const menuRefs = {};
const toggleMenu = (detailId, event) => {
    event.stopPropagation();
    menuRefs[detailId]?.toggle(event);
};
const getMenuItems = (detail) => [
    {
        label: 'Редактировать лимиты',
        icon: 'pi pi-pencil',
        command: () => openEditModal(detail),
    },
    {
        label: 'Архивировать',
        icon: 'pi pi-inbox',
        command: () => archiveDetail(detail),
    },
];

// Фильтры
const filterVisible = ref(false);
const filterName = ref(props.filters.filter_name ?? '');
const filterGatewayId = ref(props.filters.filter_gateway_id ?? null);

// Пагинация
const paginatorFirst = computed(() => (paginationData.value.current_page - 1) * paginationData.value.per_page);

const applyFilters = () => {
    filterVisible.value = false;
    router.visit(route(route().current()), {
        data: {
            filter_name: filterName.value || undefined,
            filter_gateway_id: filterGatewayId.value || undefined,
        },
        preserveScroll: true,
    });
};

const clearFilters = () => {
    filterName.value = '';
    filterGatewayId.value = null;
    filterVisible.value = false;
    router.visit(route(route().current()), { preserveScroll: true });
};

const onPageChange = (event) => {
    const page = Math.floor(event.first / event.rows) + 1;
    router.visit(route(route().current()), {
        data: {
            page,
            filter_name: filterName.value || undefined,
            filter_gateway_id: filterGatewayId.value || undefined,
        },
        preserveScroll: true,
    });
};

// Форматирование
const formatMoney = (val) => {
    const num = parseFloat(val) || 0;
    return num.toLocaleString('ru-RU');
};

const formatDetail = (detail) => {
    if (!detail.detail) return '—';
    if (detail.detail_type === 'card') {
        const str = String(detail.detail).replace(/\D/g, '');
        return str.match(/.{1,4}/g)?.join(' ') || str;
    }
    return detail.detail;
};

const getProgressPercent = (detail) => {
    const limit = parseFloat(detail.daily_limit) || 0;
    const used = parseFloat(detail.current_daily_limit) || 0;
    if (!limit) return 0;
    return Math.min(100, Math.round(used / limit * 100));
};

const getRemainingLimit = (detail) => {
    const limit = parseFloat(detail.daily_limit) || 0;
    const used = parseFloat(detail.current_daily_limit) || 0;
    return Math.max(0, limit - used);
};

const getProgressColor = (percent) => {
    if (percent >= 90) return 'bg-red-500';
    if (percent >= 70) return 'bg-primary/60';
    return 'bg-primary';
};

// Переключение активности реквизита (без Inertia)
const toggleDetailActive = async (detail, event) => {
    event.stopPropagation();
    if (toggleLoadingId.value === detail.id) return;
    toggleLoadingId.value = detail.id;
    try {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
        const response = await fetch(route('trader.white-triangles.toggle-active', detail.id), {
            method: 'PATCH',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
                'Content-Type': 'application/json',
            },
        });
        const data = await response.json();
        const idx = paymentDetailsList.value.findIndex(d => d.id === detail.id);
        if (idx !== -1) {
            paymentDetailsList.value[idx] = {
                ...paymentDetailsList.value[idx],
                is_active: data.is_active,
                is_online: data.is_active,
            };
        }
        if (selectedDetail.value?.id === detail.id) {
            selectedDetail.value = { ...selectedDetail.value, is_active: data.is_active };
        }
    } finally {
        toggleLoadingId.value = null;
    }
};

// Архивирование реквизита
const archiveDetail = (detail) => {
    if (archivingId.value) return;
    archivingId.value = detail.id;
    router.post(route('payment-details.archive', detail.id), {}, {
        preserveScroll: true,
        onSuccess: () => {
            if (selectedDetail.value?.id === detail.id) {
                selectedDetail.value = null;
            }
        },
        onFinish: () => {
            archivingId.value = null;
        },
    });
};

// Выбор реквизита
const selectDetail = (detail) => {
    selectedDetail.value = detail;
    mobileView.value = 'orders';
};

// Сделки выбранного реквизита
const selectedDetailOrders = computed(() => {
    if (!selectedDetail.value) return [];
    return ordersList.value.filter(o => o.payment_detail_id === selectedDetail.value.id);
});

// Авто-обновление сделок каждые 5 секунд
const refreshInterval = ref(null);
const isRefreshing = ref(false);

const fetchOrders = () => {
    if (isRefreshing.value || !selectedDetail.value) return;
    isRefreshing.value = true;
    router.reload({
        only: ['orders'],
        preserveScroll: true,
        onFinish: () => { isRefreshing.value = false; },
    });
};

watch(selectedDetail, (newVal) => {
    if (refreshInterval.value) {
        clearInterval(refreshInterval.value);
        refreshInterval.value = null;
    }
    if (newVal) {
        refreshInterval.value = setInterval(fetchOrders, 5000);
    }
});

onUnmounted(() => {
    if (refreshInterval.value) clearInterval(refreshInterval.value);
});

// Форма добавления реквизита
const addForm = useForm({
    detail_type: null,
    payment_gateway_id: null,
    detail: '',
    name: '',
    daily_limit: null,
    min_order_amount: null,
    max_order_amount: null,
});

const detailTypes = [
    { label: 'Карта', value: 'card' },
    { label: 'Телефон', value: 'phone' },
];

const filteredGateways = computed(() => {
    if (!addForm.detail_type) return props.paymentGateways;
    return props.paymentGateways.filter(g => g.detail_types?.includes(addForm.detail_type));
});

const openAddModal = () => {
    addForm.reset();
    showAddModal.value = true;
};

const submitAdd = () => {
    addForm.post(route('trader.white-triangles.store'), {
        preserveScroll: true,
        onSuccess: () => { showAddModal.value = false; },
    });
};

// Форма редактирования лимитов
const editForm = useForm({
    daily_limit: null,
    min_order_amount: null,
    max_order_amount: null,
});

const openEditModal = (detail) => {
    editingDetail.value = detail;
    editForm.daily_limit = detail.daily_limit_raw ?? null;
    editForm.min_order_amount = detail.min_order_amount_raw ?? null;
    editForm.max_order_amount = detail.max_order_amount_raw ?? null;
    editForm.clearErrors();
    showEditModal.value = true;
};

const submitEdit = () => {
    editForm.patch(route('trader.white-triangles.update-limits', editingDetail.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            showEditModal.value = false;
        },
    });
};

const confirmPayment = (order) => {
    confirmingOrderId.value = order.id;
    router.post(route('trader.white-triangles.confirm', order.id), {}, {
        preserveScroll: true,
        onFinish: () => { confirmingOrderId.value = null; },
    });
};

defineOptions({ layout: AuthenticatedLayout });
</script>

<template>
    <div class="h-full">
        <Head title="Белые треугольники" />

        <div class="mx-auto h-full flex flex-col">
            <!-- Заголовок -->
            <div class="flex justify-between items-center mb-4 flex-shrink-0">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white sm:text-2xl">
                    Белые треугольники
                </h2>
            </div>

            <!-- Двухколоночный лейаут -->
            <div class="flex gap-4 flex-1 min-h-0">

                <!-- ===== ЛЕВАЯ КОЛОНКА: Реквизиты ===== -->
                <div
                    class="w-full md:w-1/3 lg:w-1/4 flex flex-col min-h-0 overflow-hidden"
                    :class="{ 'hidden md:flex': mobileView === 'orders' }"
                >
                    <!-- Фильтр + кнопка добавить -->
                    <div class="flex gap-2 mb-3 flex-shrink-0">
                        <Button
                            label="Фильтры"
                            icon="pi pi-filter"
                            size="small"
                            severity="secondary"
                            class="flex-1"
                            @click="filterVisible = true"
                        />
                        <Button
                            icon="pi pi-plus"
                            size="small"
                            @click="openAddModal"
                            v-tooltip.top="'Добавить реквизит'"
                        />
                    </div>

                    <!-- Панель фильтров -->
                    <Sidebar v-model:visible="filterVisible" position="right" class="w-full md:w-1/2 lg:w-1/3">
                        <template #header>
                            <h3 class="text-lg font-semibold">Фильтры реквизитов</h3>
                        </template>
                        <div class="space-y-4 p-2">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Поиск по имени / реквизиту</label>
                                <InputText v-model="filterName" placeholder="Иванов / 4111..." class="w-full" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Платёжный метод</label>
                                <Dropdown
                                    v-model="filterGatewayId"
                                    :options="[{ id: null, name: 'Все' }, ...props.paymentGateways]"
                                    optionLabel="name"
                                    optionValue="id"
                                    class="w-full"
                                />
                            </div>
                        </div>
                        <template #footer>
                            <div class="flex justify-end gap-2">
                                <Button label="Сбросить" icon="pi pi-filter-slash" severity="danger" outlined @click="clearFilters" />
                                <Button label="Применить" icon="pi pi-check" severity="info" @click="applyFilters" />
                            </div>
                        </template>
                    </Sidebar>

                    <!-- Список реквизитов -->
                    <div class="flex-1 min-h-0 overflow-y-auto space-y-2 pr-1">
                        <!-- Пустой список -->
                        <div
                            v-if="paymentDetailsList.length === 0"
                            class="flex flex-col items-center justify-center h-40 text-gray-400 dark:text-gray-500"
                        >
                            <i class="pi pi-id-card text-4xl mb-2 opacity-40"></i>
                            <p class="text-sm">Реквизиты не найдены</p>
                        </div>

                        <!-- Карточка реквизита -->
                        <div
                            v-for="detail in paymentDetailsList"
                            :key="detail.id"
                            @click="selectDetail(detail)"
                            class="requisite-card cursor-pointer rounded-xl p-3 border transition-all duration-150"
                            :class="[
                                selectedDetail?.id === detail.id
                                    ? 'border-primary-500 bg-primary-50 dark:bg-primary-900/20 shadow-sm'
                                    : 'border-surface-200 dark:border-surface-700 bg-white dark:bg-surface-800 hover:border-primary-300 dark:hover:border-primary-700'
                            ]"
                        >
                            <!-- Строка 1: Логотип + ФИО + реквизит + кнопки -->
                            <div class="flex items-start gap-2 mb-2">
                                <div class="w-7 h-7 flex-shrink-0 flex items-center justify-center mt-0.5">
                                    <GatewayLogo
                                        :img_path="detail.payment_gateway?.logo_path"
                                        :name="detail.payment_gateway?.name"
                                        class="w-7 h-7 object-contain"
                                    />
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="font-medium text-sm text-gray-900 dark:text-white truncate">
                                        {{ detail.name }}
                                    </div>
                                    <div class="text-xs text-gray-400 dark:text-gray-500 font-mono truncate">
                                        {{ formatDetail(detail) }}
                                    </div>
                                </div>

                                <!-- Кнопки управления -->
                                <div class="flex gap-1 flex-shrink-0" @click.stop>
                                    <!-- Play / Pause -->
                                    <button
                                        class="w-7 h-7 rounded flex items-center justify-center transition-colors flex-shrink-0"
                                        :class="detail.is_active
                                            ? 'bg-primary/12 text-primary hover:bg-primary/20'
                                            : 'bg-red-100 text-red-500 dark:bg-red-900/30 dark:text-red-400 hover:bg-red-200 dark:hover:bg-red-900/50'"
                                        :disabled="toggleLoadingId === detail.id"
                                        @click="toggleDetailActive(detail, $event)"
                                    >
                                        <i v-if="toggleLoadingId !== detail.id"
                                            :class="detail.is_active ? 'pi pi-play' : 'pi pi-pause'"
                                            class="text-[11px]"
                                        ></i>
                                        <i v-else class="pi pi-spin pi-spinner text-[11px]"></i>
                                    </button>

                                    <!-- Три точки -->
                                    <button
                                        class="w-7 h-7 rounded flex items-center justify-center text-gray-400 dark:text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors flex-shrink-0"
                                        @click="toggleMenu(detail.id, $event)"
                                    >
                                        <i class="pi pi-ellipsis-v text-[11px]"></i>
                                    </button>
                                    <Menu
                                        :ref="el => { if (el) menuRefs[detail.id] = el; else delete menuRefs[detail.id]; }"
                                        :model="getMenuItems(detail)"
                                        popup
                                    />
                                </div>
                            </div>

                            <!-- Строка 2: мин.сумма / сделки | остаток лимита -->
                            <div class="flex items-center justify-between text-xs mb-1.5">
                                <span class="text-gray-500 dark:text-gray-400">
                                    {{ detail.min_order_amount ? formatMoney(detail.min_order_amount) + ' ₽' : '—' }}
                                    <span class="opacity-40 mx-0.5">/</span>
                                    <span class="font-semibold text-gray-700 dark:text-gray-300">{{ formatMoney(detail.deals_amount) }} ₽</span>
                                </span>
                                <span class="text-gray-600 dark:text-gray-400 font-medium">
                                    {{ formatMoney(getRemainingLimit(detail)) }} ₽
                                </span>
                            </div>

                            <!-- Прогрессбар -->
                            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-1.5 overflow-hidden">
                                <div
                                    class="h-full rounded-full transition-all duration-300"
                                    :class="getProgressColor(getProgressPercent(detail))"
                                    :style="{ width: getProgressPercent(detail) + '%' }"
                                ></div>
                            </div>
                        </div>
                    </div>

                    <!-- Пагинация (серверная) -->
                    <div v-if="paginationData.last_page > 1" class="flex-shrink-0 pt-2">
                        <Paginator
                            :first="paginatorFirst"
                            :rows="paginationData.per_page"
                            :totalRecords="paginationData.total"
                            :rowsPerPageOptions="[]"
                            template="PrevPageLink CurrentPageReport NextPageLink"
                            currentPageReportTemplate="{currentPage} из {totalPages}"
                            @page="onPageChange"
                        />
                    </div>
                </div>

                <!-- ===== ПРАВАЯ КОЛОНКА: Сделки ===== -->
                <div
                    class="flex-1 flex flex-col min-h-0 overflow-hidden"
                    :class="{ 'hidden md:flex': mobileView === 'requisites' }"
                >
                    <Card
                        class="flex-1 flex flex-col min-h-0"
                        :pt="{
                            root: { class: 'flex flex-col flex-1 min-h-0 overflow-hidden' },
                            body: { class: 'flex flex-col flex-1 min-h-0 overflow-hidden p-4' },
                            content: { class: 'flex-1 min-h-0 overflow-hidden flex flex-col' }
                        }"
                    >
                        <template #title>
                            <div class="flex items-center justify-between flex-shrink-0 pb-1">
                                <div class="flex items-center gap-2">
                                    <span v-if="selectedDetail" class="text-base font-semibold dark:text-white">
                                        Сделки:
                                        <span class="text-primary-600 dark:text-primary-400">{{ selectedDetail.name }}</span>
                                    </span>
                                    <span v-else class="text-base font-semibold text-gray-500 dark:text-gray-400">
                                        Выберите реквизит для просмотра сделок
                                    </span>
                                    <!-- Индикатор обновления -->
                                    <span v-if="selectedDetail && isRefreshing" class="text-xs text-gray-400 dark:text-gray-500 flex items-center gap-1">
                                        <i class="pi pi-spin pi-refresh text-[10px]"></i>
                                    </span>
                                </div>
                                <Button
                                    v-if="selectedDetail"
                                    icon="pi pi-arrow-left"
                                    label="Назад"
                                    size="small"
                                    severity="secondary"
                                    text
                                    @click="mobileView = 'requisites'; selectedDetail = null"
                                />
                            </div>
                        </template>

                        <template #content>
                            <!-- Реквизит не выбран -->
                            <div
                                v-if="!selectedDetail"
                                class="flex flex-col items-center justify-center h-full text-gray-400 dark:text-gray-500 py-16"
                            >
                                <i class="pi pi-hand-pointer text-5xl mb-4 opacity-30"></i>
                                <p class="text-lg font-medium mb-1">Реквизит не выбран</p>
                                <p class="text-sm">Выберите реквизит из списка слева</p>
                            </div>

                            <!-- Сделки -->
                            <template v-else>
                                <!-- Нет сделок -->
                                <div
                                    v-if="selectedDetailOrders.length === 0"
                                    class="flex flex-col items-center justify-center flex-1 text-gray-400 dark:text-gray-500 py-12"
                                >
                                    <i class="pi pi-inbox text-5xl mb-4 opacity-30"></i>
                                    <p class="text-lg font-medium mb-1">Сделок нет</p>
                                    <p class="text-sm">По данному реквизиту сделок не найдено</p>
                                </div>

                                <!-- Список сделок -->
                                <div v-else class="flex-1 min-h-0 overflow-y-auto space-y-2">
                                    <div
                                        v-for="order in selectedDetailOrders"
                                        :key="order.id"
                                        class="border border-surface-200 dark:border-surface-700 rounded-lg p-3 bg-surface-50 dark:bg-surface-800 hover:bg-surface-100 dark:hover:bg-surface-700 transition-colors cursor-pointer"
                                        @click="openOrderModal(order)"
                                    >
                                        <!-- Строка 1: UUID | Статус | Глазик | Подтвердить -->
                                        <div class="flex items-center gap-2 mb-2">
                                            <div class="flex-1 min-w-0">
                                                <DisplayUUID :uuid="order.uuid" class="text-xs text-muted-foreground" />
                                            </div>
                                            <OrderStatus :status="order.status" :status_name="order.status_name" class="flex-shrink-0" />
                                            <Button
                                                icon="pi pi-eye"
                                                text
                                                rounded
                                                size="small"
                                                class="flex-shrink-0"
                                                v-tooltip.top="'Просмотреть'"
                                                @click.stop="openOrderModal(order)"
                                            />
                                            <Button
                                                v-if="order.status === 'pending'"
                                                icon="pi pi-check"
                                                severity="success"
                                                text
                                                rounded
                                                size="small"
                                                class="flex-shrink-0"
                                                v-tooltip.top="'Подтвердить'"
                                                :loading="confirmingOrderId === order.id"
                                                :disabled="confirmingOrderId === order.id"
                                                @click.stop="confirmPayment(order)"
                                            />
                                        </div>

                                        <!-- Строка 2: Сумма + Прибыль -->
                                        <div class="flex items-center gap-4 mb-2">
                                            <span class="font-semibold text-foreground whitespace-nowrap">
                                                {{ order.amount }} {{ order.currency?.toUpperCase() }}
                                            </span>
                                            <span class="text-sm font-medium text-foreground whitespace-nowrap">
                                                {{ order.total_profit }} {{ order.base_currency?.toUpperCase() }}
                                            </span>
                                        </div>

                                        <!-- Строка 3: Дата -->
                                        <DateTime class="justify-start text-sm" :data="order.created_at" />
                                    </div>
                                </div>
                            </template>
                        </template>
                    </Card>
                </div>
            </div>
        </div>

        <!-- ===== Добавить реквизит ===== -->
        <Dialog
            v-model:visible="showAddModal"
            modal
            header="Добавить реквизит"
            :pt="{
                root: { class: 'border-none w-full max-w-md' },
                mask: { class: 'backdrop-blur-[2px]' }
            }"
            :closable="true"
            @hide="addForm.reset()"
        >
            <div class="space-y-4 py-2">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Тип реквизита <span class="text-red-500">*</span>
                    </label>
                    <Dropdown
                        v-model="addForm.detail_type"
                        :options="detailTypes"
                        optionLabel="label"
                        optionValue="value"
                        placeholder="Выберите тип"
                        class="w-full"
                        :class="{ 'p-invalid': addForm.errors.detail_type }"
                    />
                    <small v-if="addForm.errors.detail_type" class="text-red-500 text-xs mt-1">{{ addForm.errors.detail_type }}</small>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Платёжный метод <span class="text-red-500">*</span>
                    </label>
                    <Dropdown
                        v-model="addForm.payment_gateway_id"
                        :options="filteredGateways"
                        optionLabel="name"
                        optionValue="id"
                        placeholder="Выберите платёжный метод"
                        class="w-full"
                        :class="{ 'p-invalid': addForm.errors.payment_gateway_id }"
                        :disabled="filteredGateways.length === 0"
                        filter
                    >
                        <template #option="slotProps">
                            <div class="flex items-center gap-2">
                                <img v-if="slotProps.option.logo_path" :src="slotProps.option.logo_path" class="w-5 h-5 object-contain" />
                                <span>{{ slotProps.option.name }}</span>
                            </div>
                        </template>
                    </Dropdown>
                    <small v-if="addForm.errors.payment_gateway_id" class="text-red-500 text-xs mt-1">{{ addForm.errors.payment_gateway_id }}</small>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Реквизит <span class="text-red-500">*</span>
                    </label>
                    <InputText
                        v-model="addForm.detail"
                        :placeholder="addForm.detail_type === 'card' ? '0000 0000 0000 0000' : '+7 (999) 999-99-99'"
                        class="w-full"
                        :class="{ 'p-invalid': addForm.errors.detail }"
                    />
                    <small v-if="addForm.errors.detail" class="text-red-500 text-xs mt-1">{{ addForm.errors.detail }}</small>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        ФИО <span class="text-red-500">*</span>
                    </label>
                    <InputText
                        v-model="addForm.name"
                        placeholder="Иванов Иван Иванович"
                        class="w-full"
                        :class="{ 'p-invalid': addForm.errors.name }"
                    />
                    <small v-if="addForm.errors.name" class="text-red-500 text-xs mt-1">{{ addForm.errors.name }}</small>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Дневной лимит (₽) <span class="text-red-500">*</span>
                    </label>
                    <InputNumber
                        v-model="addForm.daily_limit"
                        placeholder="100 000"
                        class="w-full"
                        :class="{ 'p-invalid': addForm.errors.daily_limit }"
                        :min="0"
                        :useGrouping="true"
                        locale="ru-RU"
                        suffix=" ₽"
                    />
                    <small v-if="addForm.errors.daily_limit" class="text-red-500 text-xs mt-1">{{ addForm.errors.daily_limit }}</small>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div class="min-w-0">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Мин. сумма сделки (₽)</label>
                        <InputNumber
                            v-model="addForm.min_order_amount"
                            placeholder="100"
                            class="w-full"
                            :class="{ 'p-invalid': addForm.errors.min_order_amount }"
                            :min="0"
                            :useGrouping="true"
                            locale="ru-RU"
                            suffix=" ₽"
                        />
                        <small v-if="addForm.errors.min_order_amount" class="text-red-500 text-xs mt-1">{{ addForm.errors.min_order_amount }}</small>
                    </div>
                    <div class="min-w-0">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Макс. сумма сделки (₽)</label>
                        <InputNumber
                            v-model="addForm.max_order_amount"
                            placeholder="50 000"
                            class="w-full"
                            :class="{ 'p-invalid': addForm.errors.max_order_amount }"
                            :min="0"
                            :useGrouping="true"
                            locale="ru-RU"
                            suffix=" ₽"
                        />
                        <small v-if="addForm.errors.max_order_amount" class="text-red-500 text-xs mt-1">{{ addForm.errors.max_order_amount }}</small>
                    </div>
                </div>
            </div>

            <template #footer>
                <Button
                    label="Отмена"
                    icon="pi pi-times"
                    severity="secondary"
                    text
                    @click="showAddModal = false"
                    :disabled="addForm.processing"
                />
                <Button
                    label="Создать реквизит"
                    icon="pi pi-plus"
                    severity="primary"
                    @click="submitAdd"
                    :loading="addForm.processing"
                    :disabled="addForm.processing"
                />
            </template>
        </Dialog>
        <!-- Попап деталей сделки (как на странице /orders) -->
        <OrderModal />

        <!-- ===== Редактирование лимитов ===== -->
        <Dialog
            v-model:visible="showEditModal"
            modal
            header="Редактировать лимиты"
            :pt="{
                root: { class: 'border-none w-full max-w-sm' },
                mask: { class: 'backdrop-blur-[2px]' }
            }"
            :closable="true"
            @hide="editForm.reset()"
        >
            <div v-if="editingDetail" class="space-y-4 py-2">
                <div class="text-sm text-gray-500 dark:text-gray-400 mb-3 flex items-center gap-2">
                    <i class="pi pi-id-card"></i>
                    <span class="font-medium text-gray-800 dark:text-gray-200">{{ editingDetail.name }}</span>
                    <span class="font-mono text-xs opacity-60">{{ formatDetail(editingDetail) }}</span>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Дневной лимит (₽) <span class="text-red-500">*</span>
                    </label>
                    <InputNumber
                        v-model="editForm.daily_limit"
                        placeholder="100 000"
                        class="w-full"
                        :class="{ 'p-invalid': editForm.errors.daily_limit }"
                        :min="1"
                        :useGrouping="true"
                        locale="ru-RU"
                        suffix=" ₽"
                    />
                    <small v-if="editForm.errors.daily_limit" class="text-red-500 text-xs mt-1">{{ editForm.errors.daily_limit }}</small>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div class="min-w-0">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Мин. сумма (₽)</label>
                        <InputNumber
                            v-model="editForm.min_order_amount"
                            placeholder="100"
                            class="w-full"
                            :class="{ 'p-invalid': editForm.errors.min_order_amount }"
                            :min="0"
                            :useGrouping="true"
                            locale="ru-RU"
                            suffix=" ₽"
                        />
                        <small v-if="editForm.errors.min_order_amount" class="text-red-500 text-xs mt-1">{{ editForm.errors.min_order_amount }}</small>
                    </div>
                    <div class="min-w-0">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Макс. сумма (₽)</label>
                        <InputNumber
                            v-model="editForm.max_order_amount"
                            placeholder="50 000"
                            class="w-full"
                            :class="{ 'p-invalid': editForm.errors.max_order_amount }"
                            :min="0"
                            :useGrouping="true"
                            locale="ru-RU"
                            suffix=" ₽"
                        />
                        <small v-if="editForm.errors.max_order_amount" class="text-red-500 text-xs mt-1">{{ editForm.errors.max_order_amount }}</small>
                    </div>
                </div>
            </div>

            <template #footer>
                <Button
                    label="Отмена"
                    icon="pi pi-times"
                    severity="secondary"
                    text
                    @click="showEditModal = false"
                    :disabled="editForm.processing"
                />
                <Button
                    label="Сохранить"
                    icon="pi pi-check"
                    severity="primary"
                    @click="submitEdit"
                    :loading="editForm.processing"
                    :disabled="editForm.processing"
                />
            </template>
        </Dialog>
    </div>
</template>

<style scoped>
.requisite-card:active {
    transform: scale(0.99);
}

:deep(.p-inputnumber) {
    display: flex;
    width: 100%;
}

:deep(.p-inputnumber-input) {
    min-width: 0;
    width: 100%;
}
</style>
