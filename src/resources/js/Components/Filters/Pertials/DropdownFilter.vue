<script setup>
import { computed } from "vue";
import { useTableFiltersStore } from "@/store/tableFilters.js";
import MultiSelect from 'primevue/multiselect'; // Импортируем MultiSelect

const tableFiltersStore = useTableFiltersStore();

const props = defineProps({
    name: {
        type: String,
        required: true // Добавляем required для ясности
    },
    title: {
        type: String,
        default: 'Фильтр'
    }
});

// Модель для v-model MultiSelect, работает с массивом
const model = computed({
    get: () => {
        const storedValue = tableFiltersStore.filters[props.name];
        // Убеждаемся, что возвращаем массив.
        // Если в сторе строка (осталось от старой логики), пытаемся ее разделить
        if (typeof storedValue === 'string' && storedValue.length > 0) {
            return storedValue.split(',');
        }
        // Если массив, возвращаем его
        if (Array.isArray(storedValue)) {
            return storedValue;
        }
        // Иначе пустой массив
        return [];
    },
    set: (val) => {
        // MultiSelect возвращает массив, его и сохраняем
        tableFiltersStore.filters[props.name] = val;
    }
});

// Получаем варианты для опций MultiSelect
const options = computed(() => {
    return tableFiltersStore.getFiltersVariants[props.name] ?? [];
});

</script>

<template>
    <!-- Используем классы PrimeFlex для сетки -->
    <div class="field col-12 md:col-4 lg:col-3">
        <MultiSelect
            v-model="model"
            :options="options"
            optionLabel="name"  
            optionValue="value" 
            :placeholder="title"
            display="chip"      
            class="w-full"      
        />
    </div>
</template>

<style scoped>
/* Стили можно добавить здесь при необходимости */
</style>