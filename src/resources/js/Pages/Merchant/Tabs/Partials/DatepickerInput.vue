<script setup>
import {computed, getCurrentInstance, onMounted, ref, watch} from "vue";
import {Datepicker} from "flowbite-datepicker";

const props = defineProps({
    modelValue: {
        type: String,
        default: '',
    },
    placeholder: {
        type: String,
        default: 'Выберите дату',
    },
    error: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['update:modelValue', 'change']);

const instance = getCurrentInstance();
const uid = instance.uid;
const datepickerRef = ref(null);

onMounted(() => {
    const datepickerElement = document.getElementById(`date-datepicker-${uid}`);

    datepickerElement.addEventListener('changeDate', (e) => {
        emit('update:modelValue', e.target.value);
        emit('change', e.target.value);
    });

    Datepicker.locales.ru = {
        days: ["Воскресенье", "Понедельник", "Вторник", "Среда", "Четверг", "Пятница", "Суббота"],
        daysShort: ["Вск", "Пнд", "Втр", "Срд", "Чтв", "Птн", "Суб"],
        daysMin: ["Вс", "Пн", "Вт", "Ср", "Чт", "Пт", "Сб"],
        months: ["Январь", "Февраль", "Март", "Апрель", "Май", "Июнь", "Июль", "Август", "Сентябрь", "Октябрь", "Ноябрь", "Декабрь"],
        monthsShort: ["Янв", "Фев", "Мар", "Апр", "Май", "Июн", "Июл", "Авг", "Сен", "Окт", "Ноя", "Дек"],
        today: "Сегодня",
        clear: "Очистить",
        format: "dd.mm.yyyy",
        weekStart: 1,
        monthsTitle: 'Месяцы'
    };

    datepickerRef.value = new Datepicker(datepickerElement, {
        language: 'ru',
        format: 'dd/mm/yyyy',
    });
});
</script>

<template>
    <div class="relative w-full">
        <div class="absolute inset-y-0 start-0 flex items-center ps-3.5 pointer-events-none">
            <svg class="w-4 h-4 text-gray-500 dark:text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                <path d="M20 4a2 2 0 0 0-2-2h-2V1a1 1 0 0 0-2 0v1h-3V1a1 1 0 0 0-2 0v1H6V1a1 1 0 0 0-2 0v1H2a2 2 0 0 0-2 2v2h20V4ZM0 18a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V8H0v10Zm5-8h10a1 1 0 0 1 0 2H5a1 1 0 0 1 0-2Z"/>
            </svg>
        </div>
        <input
            datepicker
            :id="`date-datepicker-${uid}`"
            type="text"
            :class="[
                'bg-gray-50 border text-gray-900 text-sm rounded-xl focus:ring-blue-500 focus:border-blue-500 block w-full ps-10 p-2.5 dark:bg-gray-700 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500',
                error ? 'border-red-500' : 'border-gray-300 dark:border-gray-600'
            ]"
            :placeholder="placeholder"
            :value="modelValue"
        >
    </div>
</template>

<style scoped>
</style> 