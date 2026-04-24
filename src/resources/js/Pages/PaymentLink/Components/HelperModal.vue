<script setup>
import { computed, ref } from "vue";
import { useFormatPaymentDetail } from "@/Utils/paymentDetail.js";
import Dialog from "primevue/dialog";
import Button from "primevue/button";

const props = defineProps({
    data: {
        type: Object,
        default: {}
    }
});

const visible = ref(false);

const formatedPaymentDetail = computed(() => {
    return useFormatPaymentDetail(props.data.detail, props.data.detail_type);
});

// Определяем страну из названия платежного шлюза
const countryInfo = computed(() => {
    const gateway = props.data.payment_gateway || '';
    
    if (gateway.toLowerCase().includes('таджикистан')) {
        return {
            name: 'Таджикистан',
            isCrossBorder: true
        };
    }
    
    if (gateway.toLowerCase().includes('узбекистан')) {
        return {
            name: 'Узбекистан', 
            isCrossBorder: true
        };
    }
    
    if (gateway.toLowerCase().includes('киргизия') || gateway.toLowerCase().includes('кыргызстан')) {
        return {
            name: 'Киргизия',
            isCrossBorder: true
        };
    }
    
    if (gateway.toLowerCase().includes('казахстан')) {
        return {
            name: 'Казахстан',
            isCrossBorder: true
        };
    }
    
    if (gateway.toLowerCase().includes('армения')) {
        return {
            name: 'Армения',
            isCrossBorder: true
        };
    }
    
    if (gateway.toLowerCase().includes('беларусь') || gateway.toLowerCase().includes('белоруссия')) {
        return {
            name: 'Беларусь',
            isCrossBorder: true
        };
    }
    
    if (gateway.toLowerCase().includes('абхазия')) {
        return {
            name: 'Абхазия',
            isCrossBorder: true
        };
    }
    
    return {
        name: 'Россия',
        isCrossBorder: false
    };
});

// Показываем модальное окно
const show = () => {
    visible.value = true;
};

// Определяем функцию в глобальной области видимости
window.showHelperModal = show;

// Методы для открытия видео
const openVideo = (url) => {
    // Пробуем разные способы открытия
    try {
        // Способ 1: window.open с дополнительными параметрами
        const newWindow = window.open(url, '_blank', 'noopener,noreferrer');
        
        // Если не удалось открыть (заблокировано браузером)
        if (!newWindow || newWindow.closed || typeof newWindow.closed == 'undefined') {
            // Способ 2: создаем временную ссылку и кликаем по ней
            const link = document.createElement('a');
            link.href = url;
            link.target = '_blank';
            link.rel = 'noopener noreferrer';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }
    } catch (error) {
        console.error('Ошибка при открытии видео:', error);
        // Запасной вариант - просто переходим по ссылке
        window.location.href = url;
    }
};
</script>

<template>
    <Dialog 
        v-model:visible="visible" 
        modal 
        :header="countryInfo.isCrossBorder ? `Как перевести деньги ${props.data.detail_type === 'card' ? 'по номеру карты' : ''} в ${countryInfo.name}?` : 'Инструкция к оплате'"
        :style="{ width: '50rem' }"
        :breakpoints="{ '1199px': '75vw', '575px': '90vw' }"
    >
        <!-- Трансграничные переводы -->
        <div v-if="countryInfo.isCrossBorder">
            
            <!-- Инструкции для карт -->
            <div v-if="data.detail_type === 'card'">
                <div class="grid grid-cols-1 gap-4 mb-6">
                    <!-- Сбербанк -->
                    <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-xl p-4">
                        <div class="flex items-start justify-between mb-4">
                            <div class="flex items-center space-x-3">
                                <div class="w-12 h-12   rounded-xl flex items-center justify-center">
                                   <img src="https://app.hillcard.net/images/sber.png" alt="Сбербанк" class="w-8 h-8">
                                </div>
                                <div>
                                    <h4 class="font-semibold text-gray-900 dark:text-white">Перевод через СБЕР</h4>
                                    <p class="text-sm text-gray-600 dark:text-gray-400">Пошаговая инструкция</p>
                                </div>
                            </div>
                            <Button 
                                icon="pi pi-play" 
                                severity="success" 
                                outlined 
                                size="small"
                                @click="openVideo('https://app.hillcard.net/sber.mp4')"
                                label="Видео"
                                class="ml-2"
                            />
                        </div>
                        <ol class="list-decimal list-inside space-y-2 text-sm text-gray-700 dark:text-gray-300">
                            <li>Скопируйте номер карты и Фамилию и Имя получателя</li>
                            <li>Зайдите в онлайн кабинет банка и перейдите в раздел "Платежи"</li>
                            <li>Выберите раздел "В другую страну" и найдите в поиске "{{ countryInfo.name }}"</li>
                            <li>В открывшемся меню выберите "На карту или счет"</li>
                            <li>В верхнем меню выберите "Карта" и вставьте номер карты получателя - "Продолжить"</li>
                            <li>Укажите точную сумму и введите Фамилию и Имя</li>
                            <li>Нажмите кнопку "Перевести" проверив предварительно сумму</li>
                        </ol>
                    </div>
                    
                    <!-- Т-Банк -->
                    <div class="bg-primary/8 border border-primary/20 rounded-xl p-4">
                        <div class="flex items-start justify-between mb-4">
                            <div class="flex items-center space-x-3">
                                <div class="w-12 h-12   rounded-xl flex items-center justify-center">
                               <img src="https://app.hillcard.net/images/tbank.png" alt="Сбербанк" class="w-8 h-8">
                                </div>
                                <div>
                                    <h4 class="font-semibold text-gray-900 dark:text-white">Перевод через Т-БАНК</h4>
                                    <p class="text-sm text-gray-600 dark:text-gray-400">Пошаговая инструкция</p>
                                </div>
                            </div>
                            <Button 
                                icon="pi pi-play" 
                                severity="warn" 
                                outlined 
                                size="small"
                                @click="openVideo('https://app.hillcard.net/t-bank.mp4')"
                                label="Видео"
                                class="ml-2"
                            />
                        </div>
                        <ol class="list-decimal list-inside space-y-2 text-sm text-gray-700 dark:text-gray-300">
                            <li>Скопируйте номер карты и ФИО получателя</li>
                            <li>Зайдите в онлайн кабинет банка и перейдите в раздел "Платежи"</li>
                            <li>В разделе "Переводы" - выберите "По номеру карты"</li>
                            <li>Вставьте номер карты и ФИО получателя</li>
                            <li>Укажите точную сумму и нажмите кнопку "Перевести"</li>
                        </ol>
                    </div>
                    
                    <!-- Альфа-Банк -->
                    <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-xl p-4">
                        <div class="flex items-start justify-between mb-4">
                            <div class="flex items-center space-x-3">
                                <div class="w-12 h-12   rounded-xl flex items-center justify-center">
                                   <img src="https://app.hillcard.net/images/alfa.png" alt="Сбербанк" class="w-8 h-8">
                                </div>
                                <div>
                                    <h4 class="font-semibold text-gray-900 dark:text-white">Перевод через Альфа-Банк</h4>
                                    <p class="text-sm text-gray-600 dark:text-gray-400">Пошаговая инструкция</p>
                                </div>
                            </div>
                            <Button 
                                icon="pi pi-play" 
                                severity="danger" 
                                outlined 
                                size="small"
                                @click="openVideo('https://app.hillcard.net/alfa.mp4')"
                                label="Видео"
                                class="ml-2"
                            />
                        </div>
                        <ol class="list-decimal list-inside space-y-2 text-sm text-gray-700 dark:text-gray-300">
                            <li>Скопируйте номер карты и ФИО получателя</li>
                            <li>Зайдите в онлайн кабинет банка и перейдите в раздел "Платежи"</li>
                            <li>Выберите раздел "В другую страну" и найдите {{ countryInfo.name }}</li>
                            <li>Выберите "На карту или счет" и вставьте номер карты</li>
                            <li>Укажите точную сумму и ФИО получателя</li>
                            <li>Нажмите кнопку "Перевести"</li>
                        </ol>
                    </div>
                </div>
                
                <div class="mt-4 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-xl border border-blue-200 dark:border-blue-800">
                    <div class="flex items-start space-x-3">
                        <i class="pi pi-info-circle text-blue-500 mt-0.5 text-xl"></i>
                        <div class="text-sm text-blue-700 dark:text-blue-300">
                            <div class="font-semibold mb-2">Данные для перевода:</div>
                            <div class="space-y-1">
                                <div><strong>Номер карты:</strong> {{ formatedPaymentDetail }}</div>
                                <div><strong>Получатель:</strong> {{ data.initials }}</div>
                                <div><strong>Сумма:</strong> {{ data.amount_formated }}{{ data.currency_symbol }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Инструкции для телефонов -->
            <div v-else-if="data.detail_type === 'phone'">
                <div class="space-y-4">
                    <div class="text-center">
                        <h3 class="text-lg font-semibold mb-4">Выберите ваш банк для перевода:</h3>
                    </div>
                    
                    <div class="grid grid-cols-1 gap-4">
                        <!-- Сбербанк -->
                        <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-xl p-4">
                            <div class="flex items-start justify-between mb-4">
                                <div class="flex items-center space-x-3">
                                    <div class="w-12 h-12 rounded-xl flex items-center justify-center">
                                        <img src="https://app.hillcard.net/images/sber.png" alt="Сбербанк" class="w-8 h-8">
                                    </div>
                                    <div>
                                        <h4 class="font-semibold text-gray-900 dark:text-white">Сбербанк</h4>
                                        <p class="text-sm text-gray-600 dark:text-gray-400">Пошаговая инструкция</p>
                                    </div>
                                </div>
                                <Button 
                                    icon="pi pi-play" 
                                    severity="success" 
                                    outlined 
                                    size="small"
                                    @click="openVideo('https://app.hillcard.net/sber.mp4')"
                                    label="Видео"
                                    class="ml-2"
                                />
                            </div>
                            <ol class="list-decimal list-inside space-y-2 text-sm text-gray-700 dark:text-gray-300">
                                <li>"Платежи" → В другую страну</li>
                                <li>{{ countryInfo.name }} → НА КАРТУ ИЛИ СЧЕТ</li>
                                <li>Выберите "{{ data.payment_gateway }} (+{{ data.detail }}). Получатель: {{ data.initials }}"</li>
                                <li>Введите сумму {{ data.amount_formated }}{{ data.currency_symbol }} → Перевести</li>
                            </ol>
                        </div>
                        
                        <!-- Т-Банк -->
                        <div class="bg-primary/8 border border-primary/20 rounded-xl p-4">
                            <div class="flex items-start justify-between mb-4">
                                <div class="flex items-center space-x-3">
                                    <div class="w-12 h-12  rounded-xl flex items-center justify-center">
                                        <img src="https://app.hillcard.net/images/tbank.png" alt="Сбербанк" class="w-8 h-8">
                                    </div>
                                    <div>
                                        <h4 class="font-semibold text-gray-900 dark:text-white">Т-Банк</h4>
                                        <p class="text-sm text-gray-600 dark:text-gray-400">Пошаговая инструкция</p>
                                    </div>
                                </div>
                                <Button 
                                    icon="pi pi-play" 
                                    severity="warn" 
                                    outlined 
                                    size="small"
                                    @click="openVideo('https://app.hillcard.net/t-bank.mp4')"
                                    label="Видео"
                                    class="ml-2"
                                />
                            </div>
                            <ol class="list-decimal list-inside space-y-2 text-sm text-gray-700 dark:text-gray-300">
                                <li>"Платежи" → В другую страну</li>
                                <li>{{ countryInfo.name }} → НА КАРТУ ИЛИ СЧЕТ</li>
                                <li>Выберите "{{ data.payment_gateway }} (+{{ data.detail }}). Получатель: {{ data.initials }}"</li>
                                <li>Введите сумму {{ data.amount_formated }}{{ data.currency_symbol }} → Перевести</li>
                            </ol>
                        </div>
                        
                        <!-- Альфабанк -->
                        <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-xl p-4">
                            <div class="flex items-start justify-between mb-4">
                                <div class="flex items-center space-x-3">
                                    <div class="w-12 h-12   rounded-xl flex items-center justify-center">
                                      <img src="https://app.hillcard.net/images/alfa.png" alt="Сбербанк" class="w-8 h-8">
                                    </div>
                                    <div>
                                        <h4 class="font-semibold text-gray-900 dark:text-white">Альфа-Банк</h4>
                                        <p class="text-sm text-gray-600 dark:text-gray-400">Пошаговая инструкция</p>
                                    </div>
                                </div>
                                <Button 
                                    icon="pi pi-play" 
                                    severity="danger" 
                                    outlined 
                                    size="small"
                                    @click="openVideo('https://app.hillcard.net/alfa.mp4')"
                                    label="Видео"
                                    class="ml-2"
                                />
                            </div>
                            <ol class="list-decimal list-inside space-y-2 text-sm text-gray-700 dark:text-gray-300">
                                <li>"Платежи" → В другую страну</li>
                                <li>{{ countryInfo.name }} → НА КАРТУ ИЛИ СЧЕТ</li>
                                <li>Выберите "{{ data.payment_gateway }} (+{{ data.detail }}). Получатель: {{ data.initials }}"</li>
                                <li>Введите сумму {{ data.amount_formated }}{{ data.currency_symbol }} → Перевести</li>
                            </ol>
                        </div>
                        
                        <!-- Другой банк -->
                        <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-xl p-4">
                            <div class="flex items-center space-x-3 mb-4">
                                <div class="w-12 h-12 bg-blue-600 rounded-xl flex items-center justify-center">
                                    <i class="pi pi-building text-white text-xl"></i>
                                </div>
                                <div>
                                    <h4 class="font-semibold text-gray-900 dark:text-white">Другой банк</h4>
                                    <p class="text-sm text-gray-600 dark:text-gray-400">Универсальная инструкция</p>
                                </div>
                            </div>
                            <div class="space-y-3">
                                <p class="text-sm text-gray-700 dark:text-gray-300 font-medium">Откройте приложение Вашего банка:</p>
                                <ol class="list-decimal list-inside space-y-2 text-sm text-gray-700 dark:text-gray-300">
                                    <li>"Платежи" → В другую страну</li>
                                    <li>{{ countryInfo.name }} → НА КАРТУ ИЛИ СЧЕТ</li>
                                    <li>Выберите "{{ data.payment_gateway }} (+{{ data.detail }}). Получатель: {{ data.initials }}"</li>
                                    <li>Введите сумму {{ data.amount_formated }}{{ data.currency_symbol }} → Перевести</li>
                                </ol>
                                <div class="p-3 bg-green-50 dark:bg-green-900/20 rounded-lg border border-green-200 dark:border-green-800 text-green-700 dark:text-green-300 text-sm">
                                    <i class="pi pi-check-circle mr-2"></i>
                                    Перевод будет отправлен аналогично переводу в другой банк!
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Обычные переводы (не трансграничные) -->
        <div v-else>
            <div class="space-y-4">
                <ol class="list-decimal list-inside space-y-3 text-gray-700 dark:text-gray-300">
                    <li class="flex items-start space-x-3">
                        <i class="pi pi-check text-green-500 mt-1"></i>
                        <span>Зайдите в свое банковское приложение</span>
                    </li>
                    <li class="flex items-start space-x-3">
                        <i class="pi pi-check text-green-500 mt-1"></i>
                        <span v-if="data.detail_type === 'card'">Скопируйте номер карты для перевода <strong>{{ formatedPaymentDetail }}</strong></span>
                        <span v-else-if="data.detail_type === 'phone'">Скопируйте номер телефона для перевода <strong>{{ formatedPaymentDetail }}</strong></span>
                        <span v-else-if="data.detail_type === 'sim'">Скопируйте номер сим-карты для перевода <strong>{{ formatedPaymentDetail }}</strong></span>
                        <span v-else>Скопируйте номер счета для перевода <strong>{{ formatedPaymentDetail }}</strong></span>
                    </li>
                    <li class="flex items-start space-x-3">
                        <i class="pi pi-check text-green-500 mt-1"></i>
                        <span v-if="data.detail_type === 'card'">В банковском приложении выберите перевод по карте</span>
                        <span v-else-if="data.detail_type === 'phone'">В банковском приложении выберите перевод по СБП</span>
                        <span v-else-if="data.detail_type === 'sim'">В банковском приложении выберите перевод по номеру сим-карты</span>
                        <span v-else>В банковском приложении выберите перевод по номеру счета</span>
                    </li>
                    <li class="flex items-start space-x-3">
                        <i class="pi pi-check text-green-500 mt-1"></i>
                        <span>Сделайте перевод точной суммы <strong>{{ data.amount_formated }}{{ data.currency_symbol }}</strong></span>
                    </li>
                    <li class="flex items-start space-x-3">
                        <i class="pi pi-check text-green-500 mt-1"></i>
                        <span>Дождитесь зачисления средств. Не закрывайте страницу до подтверждения успешной оплаты.</span>
                    </li>
                </ol>
                
                <div class="p-4 bg-red-50 dark:bg-red-900/20 rounded-lg border border-red-200 dark:border-red-800">
                    <div class="flex items-start space-x-3">
                        <i class="pi pi-exclamation-triangle text-red-500 mt-0.5"></i>
                        <div class="text-sm text-red-700 dark:text-red-300">
                            <strong>Запрещено:</strong> Оплачивать заявку несколькими переводами. В случае
                            несоблюдений рекомендаций заявка будет отменена, а средства будут утеряны
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <template #footer>
            <Button 
                label="Закрыть" 
                @click="visible = false" 
                class="w-full"
                severity="primary"
            />
        </template>
    </Dialog>
</template>

<style scoped>
:deep(.p-accordion-header-link) {
    padding: 1rem;
}

:deep(.p-accordion-content) {
    padding: 1rem;
}

/* Исправляем стили для кнопки warn */
:deep(.p-button-outlined.p-button-warn) {
    background: var(--p-button-warn-background) !important;
    border: 1px solid var(--p-button-warn-border-color) !important;
    color: var(--p-button-warn-color) !important;
}

 
</style>
