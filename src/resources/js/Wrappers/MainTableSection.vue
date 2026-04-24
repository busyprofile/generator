<script setup>
import {router, usePage} from "@inertiajs/vue3";
import {computed, ref, getCurrentInstance} from "vue";
import Pagination from "@/Components/Pagination/Pagination.vue";
import AlertError from "@/Components/Alerts/AlertError.vue";
import AlertInfo from "@/Components/Alerts/AlertInfo.vue";
import Dropdown from 'primevue/dropdown';
import {useTableFiltersStore} from "@/store/tableFilters.js";

const tableFiltersStore = useTableFiltersStore();

const props = defineProps({
    title: {
        type: String,
    },
    data: {
        type: Object,
        default: {}
    },
    paginate: {
        type: Boolean,
        default: true
    },
    displayPagination: {
        type: Boolean,
        default: true
    }
});

tableFiltersStore.setMeta(props.data?.meta);
tableFiltersStore.setFilters(usePage().props.filters);
tableFiltersStore.setTab(new URL(window.location.href).searchParams.get('tab') || '');
tableFiltersStore.setFiltersVariants(usePage().props.filtersVariants);

const items = computed(() => {
    if (props.paginate) {
        return props.data.data;
    }
    return props.data;
});

const perPageOptions = [
    { value: 6, name: '6 строк' },
    { value: 9, name: '9 строк' },
    { value: 15, name: '15 строк' },
    { value: 21, name: '21 строк' },
    { value: 27, name: '27 строк' },
    { value: 51, name: '51 строка' },
    { value: 99, name: '99 строк' }
];

const selectedPerPage = computed({
    get: () => tableFiltersStore.getPerPage,
    set: (newValue) => {
        tableFiltersStore.setPerPage(newValue);
        tableFiltersStore.setCurrentPage(1);
        openPage();
    }
});

const changeCurrentPage = (value) => {
    tableFiltersStore.setCurrentPage(value ?? 1);
    openPage();
}

const openPage = () => {
    router.visit(route(route().current()), {
        data: tableFiltersStore.getQueryData,
        preserveScroll: true
    })
}


const {uid} = getCurrentInstance();

const hasPendingDisputes = ref(usePage().props.data.hasPendingDisputes);

router.on('success', (event) => {
    hasPendingDisputes.value = usePage().props.data.hasPendingDisputes;
})
</script>

<template>
    <div>
        <div>
            <div class="mx-auto space-y-6">
                <div class="flex justify-between items-center">
                    <h2 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">{{ title }}</h2>
                    <slot name="button"></slot>
                </div>

                <AlertError v-if="hasPendingDisputes" message="У вас есть не закрытый спор!"></AlertError>

                <AlertError :message="$page.props.flash.error"></AlertError>
                <AlertInfo :message="$page.props.flash.message"></AlertInfo>

                <div>
                    <slot name="header"/>
                </div>
                <div>
                    <slot name="table-filters"/>
                </div>
                <div>
                    <slot name="custom-actions"></slot>
                </div>
                <div>

                    <slot v-if="items.length" name="body"/>
                    <div v-else class="flex flex-col items-center justify-center py-16 text-gray-400 dark:text-gray-500">
                        <i class="pi pi-inbox text-5xl mb-3 opacity-30"></i>
                        <p class="text-base font-medium">Пока что тут пусто</p>
                    </div>
                </div>
                <div v-if="paginate && displayPagination" class="flex flex-row justify-between items-center gap-4">
                    <Pagination
                        v-model="tableFiltersStore.page"
                        :total-items="tableFiltersStore.getTotal"
                        previous-label="Назад" next-label="Вперед"
                        @page-changed="changeCurrentPage"
                        :per-page="tableFiltersStore.getPerPage"
                    ></Pagination>

                    <div class="flex items-center gap-2">
                        <Dropdown
                            :id="'perPageSelect-'+uid"
                            v-model="selectedPerPage"
                            :options="perPageOptions"
                            optionLabel="name"
                            optionValue="value"
                            class="py-1 text-sm"
                        />
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.p-select {
    background-color: var(--surface-section) !important;
}
</style>
