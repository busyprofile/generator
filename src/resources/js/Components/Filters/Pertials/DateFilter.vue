<script setup>
import {computed} from "vue";
import Calendar from 'primevue/calendar';
import {useTableFiltersStore} from "@/store/tableFilters.js";

const tableFiltersStore = useTableFiltersStore();

const props = defineProps({
    name: {
        type: String,
    },
    title: {
        type: String,
    },
});

// Функция для форматирования даты в строку формата dd/mm/yy
const formatDate = (date) => {
    if (!date) return null;
    const day = date.getDate().toString().padStart(2, '0');
    const month = (date.getMonth() + 1).toString().padStart(2, '0');
    const year = date.getFullYear().toString().substr(-2);
    return `${day}/${month}/${year}`;
};

const model = computed({
    get: () => tableFiltersStore.filters[props.name],
    set: (val) => {
        // Если значение - объект Date, преобразуем его в строку нужного формата
        const formattedValue = val instanceof Date ? formatDate(val) : val;
        tableFiltersStore.filters[props.name] = formattedValue;
    }
});
</script>

<template>
    <!-- Using field and col-* for layout within PrimeFlex grid -->
    <div class="field col-12 md:col-4 lg:col-3">
        <Calendar
            v-model="model"
            :placeholder="title"
            dateFormat="dd/mm/yy" 
            showIcon
            class="w-full" 
        />
    </div>
</template>

<style scoped>

</style>
