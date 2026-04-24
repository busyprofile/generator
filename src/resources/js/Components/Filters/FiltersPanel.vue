<script setup>
import {ref, computed, onMounted, onUnmounted} from "vue";
import {router} from "@inertiajs/vue3";
import {useTableFiltersStore} from "@/store/tableFilters.js";
import Button from 'primevue/button';
import Sidebar from 'primevue/sidebar';

const tableFiltersStore = useTableFiltersStore();

const props = defineProps({
    name: {
        type: String,
    },
    query: {
        type: Object,
        default: {}
    }
});

// State for Sidebar visibility
const isFiltersVisible = ref(false);

// --- Responsive Button Size Logic ---
const screenWidth = ref(window.innerWidth);

const updateScreenWidth = () => {
    screenWidth.value = window.innerWidth;
};

onMounted(() => {
    window.addEventListener('resize', updateScreenWidth);
});

onUnmounted(() => {
    window.removeEventListener('resize', updateScreenWidth);
});

// Computed property for button size based on screen width (md breakpoint: 768px)
const buttonSize = computed(() => {
    return screenWidth.value < 768 ? 'small' : null;
});
// --- End Responsive Button Size Logic ---

const applyFilters = () => {
    tableFiltersStore.setCurrentPage(1);

    router.visit(route(route().current()), {
        data: {
            ...tableFiltersStore.getQueryData,
            ...props.query
        },
        preserveScroll: true
    })
}

const clearFilters = () => {
    tableFiltersStore.setCurrentPage(1);
    tableFiltersStore.setFilters({});

    router.visit(route(route().current()), {
        data: {
            ...tableFiltersStore.getQueryData,
            ...props.query
        },
        preserveScroll: true
    })
}
</script>

<template>
    <section>
        <!-- Button to toggle the Sidebar -->
        <Button
            label="Фильтры"
            icon="pi pi-filter"
            @click="isFiltersVisible = true"
            :size="buttonSize"
            class=""
        />

        <!-- Sidebar for Filters -->
        <Sidebar v-model:visible="isFiltersVisible" position="right" class="w-full md:w-1/2 lg:w-1/3" :pt="{ content: { class: 'pb-20' } }">
            <template #header>
                <h3 class="text-lg font-semibold">Фильтры</h3>
            </template>

            <div class="p-fluid grid formgrid gap-3">
                <slot/>
            </div>

            <template #footer>
                <div class="flex justify-end gap-2">
                    <Button
                        label="Очистить"
                        icon="pi pi-filter-slash"
                        @click="clearFilters(); isFiltersVisible = false;"
                        severity="danger"
                        outlined
                    />
                    <Button
                        label="Применить"
                        icon="pi pi-check"
                        @click="applyFilters(); isFiltersVisible = false;"
                        severity="info"
                        autofocus
                    />
                </div>
            </template>
        </Sidebar>
    </section>
</template>

<style scoped>
</style>
