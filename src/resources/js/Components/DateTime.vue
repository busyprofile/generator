<script setup>
import {computed} from "vue";
import {useClipboard} from "@vueuse/core";
import Button from 'primevue/button';

const props = defineProps({
    data: {
        type: String,
    },
    plural: {
        type: Boolean,
        default: null,
    },
});

const formatDateRelative = (dateString) => {
    // Создаем дату из строки как московское время (UTC+3)
    const moscowDate = new Date(dateString);
    
    // Получаем разницу между московским временем и локальным временем пользователя в минутах
    const moscowOffset = 3 * 60; // Москва UTC+3 (в минутах)
    const localOffset = new Date().getTimezoneOffset() * -1; // Локальное смещение в минутах (с обратным знаком)
    const offsetDiff = moscowOffset - localOffset; // Разница в минутах
    
    // Корректируем дату с учетом разницы часовых поясов
    const correctedDate = new Date(moscowDate.getTime() - offsetDiff * 60 * 1000);
    
    const now = new Date();
    const diffInSeconds = Math.floor((now - correctedDate) / 1000);

    const intervals = {
        'год': 31536000,
        'месяц': 2592000,
        'неделя': 604800,
        'день': 86400,
        'час': 3600,
        'минута': 60,
        'секунда': 1,
    };

    for (const [unit, seconds] of Object.entries(intervals)) {
        const interval = Math.floor(diffInSeconds / seconds);
        if (interval >= 1) {
            if (unit === 'секунда' && interval < 5) {
                return 'только что';
            }
            return `${interval} ${getPluralForm(interval, unit)} назад`;
        }
    }

    return 'только что';
}

const getPluralForm = (number, unit) => {
    const pluralRules = {
        'год': ['год', 'года', 'лет'],
        'месяц': ['месяц', 'месяца', 'месяцев'],
        'неделя': ['неделя', 'недели', 'недель'],
        'день': ['день', 'дня', 'дней'],
        'час': ['час', 'часа', 'часов'],
        'минута': ['минута', 'минуты', 'минут'],
        'секунда': ['секунда', 'секунды', 'секунд'],
    };

    const cases = [2, 0, 1, 1, 1, 2];
    return pluralRules[unit][
        number % 100 > 4 && number % 100 < 20
            ? 2
            : cases[Math.min(number % 10, 5)]
        ];
}

const formatedData = computed(() => {
    return props.plural ? formatDateRelative(props.data) : props.data;
});

const { copy, copied, isSupported } = useClipboard();

// Tooltip text for copy button
const copyTooltipText = computed(() => copied.value ? 'Скопировано!' : 'Скопировать');
</script>

<template>
    <div class="flex align-items-center gap-2">
       
        <span class="text-sm text-color text-nowrap" v-tooltip.top="data">
            {{ formatedData }}
        </span>
        <!-- <Button 
            v-if="isSupported"
            icon="pi pi-copy"
            text 
            rounded 
            severity="secondary"
            class="" 
            @click.stop="copy(data)" 
            v-tooltip.top="copyTooltipText"
            size="small"
        /> -->
    </div>
</template>

<style scoped>

</style>
