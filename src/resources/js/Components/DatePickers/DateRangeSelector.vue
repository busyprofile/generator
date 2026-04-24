<script setup>
import {ref, computed} from "vue";
import DatePickerInput from "@/Components/DatePickers/DatePickerInput.vue";

const props = defineProps({
    startDate: {
        type: String,
        default: '',
    },
    endDate: {
        type: String,
        default: '',
    },
    startPlaceholder: {
        type: String,
        default: 'Начальная дата',
    },
    endPlaceholder: {
        type: String,
        default: 'Конечная дата',
    },
});

const emit = defineEmits(['update:startDate', 'update:endDate', 'change']);

const startDateModel = computed({
    get: () => props.startDate,
    set: (value) => emit('update:startDate', value)
});

const endDateModel = computed({
    get: () => props.endDate,
    set: (value) => emit('update:endDate', value)
});

const handleStartDateChange = (value) => {
    emit('change', { startDate: value, endDate: props.endDate });
};

const handleEndDateChange = (value) => {
    emit('change', { startDate: props.startDate, endDate: value });
};
</script>

<template>
    <div class="flex flex-wrap md:flex-nowrap gap-4">
        <div class="w-full md:w-1/2">
            <DatePickerInput
                v-model="startDateModel"
                :placeholder="startPlaceholder"
                @change="handleStartDateChange"
            />
        </div>
        <div class="w-full md:w-1/2">
            <DatePickerInput
                v-model="endDateModel"
                :placeholder="endPlaceholder"
                @change="handleEndDateChange"
            />
        </div>
    </div>
</template>

<style scoped>
</style> 