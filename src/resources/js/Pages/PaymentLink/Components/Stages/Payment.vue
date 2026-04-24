<script setup>
import CopyPaymentText from "@/Components/CopyPaymentText.vue";
import Button from "primevue/button";
import {computed} from "vue";
import {useFormatPaymentDetail} from "@/Utils/paymentDetail.js";
import Card from "primevue/card";
import GatewayLogo from "@/Components/GatewayLogo.vue";
import Divider from "primevue/divider";

const props = defineProps({
    data: {
        type: Object,
        default: {}
    },
});

// Отладка данных
console.log('Payment component data:', props.data);
console.log('payment_gateway:', props.data.payment_gateway);
console.log('payment_gateway_logo_path:', props.data.payment_gateway_logo_path);

const formatedPaymentDetail = computed(() => {
    return useFormatPaymentDetail(props.data.detail, props.data.detail_type);
})

const copyToClipboard = async (text) => {
    try {
        // Современный Clipboard API
        if (navigator.clipboard && navigator.clipboard.writeText) {
            await navigator.clipboard.writeText(text);
        } else {
            // Fallback для старых браузеров
            const textArea = document.createElement('textarea');
            textArea.value = text;
            textArea.style.position = 'fixed';
            textArea.style.left = '-999999px';
            textArea.style.top = '-999999px';
            document.body.appendChild(textArea);
            textArea.focus();
            textArea.select();
            document.execCommand('copy');
            document.body.removeChild(textArea);
        }
        console.log('Текст скопирован:', text);
    } catch (err) {
        console.error('Ошибка копирования:', err);
        // Показываем пользователю текст для ручного копирования
        alert(`Скопируйте текст вручную: ${text}`);
    }
}

const openHelperModal = () => {
    if (window.showHelperModal) {
        window.showHelperModal();
    }
}
</script>

<template>
    <div class="sm:pb-3">
        <div
          v-if="data.detail_type === 'phone'" > 
        <div
         
            class="flex items-center sm:text-xl text-sm text-gray-900 dark:text-gray-200 sm:mb-0 mb-3"
        >
            <img src="/images/sbp.svg" class="mr-2 w-8 h-8">
            Быстрая оплата или СБП
        </div>
        <div v-if="data.detail_type === 'account_number'" class="flex items-center p-3 mb-4 sm:text-sm text-xs text-primary border border-primary/30 rounded-xl bg-primary/8">
            <svg class="flex-shrink-0 inline w-4 h-4 me-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                <path d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5ZM9.5 4a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3ZM12 15H8a1 1 0 0 1 0-2h1v-3H8a1 1 0 0 1 0-2h2a1 1 0 0 1 1 1v4h1a1 1 0 0 1 0 2Z"/>
            </svg>
            <div>
                Внимание это перевод по счету, а не на карту!
            </div>
               
        </div>
   <Divider />
   </div>
        <div class="sm:my-5 sm:text-base text-sm space-y-3">
            <!-- Основная информация о получателе и реквизитах -->
            <div class="flex items-center justify-between">  
            <div class="  text-xl font-medium text-gray-900 dark:text-white ">Отправьте перевод на <br>{{ data.payment_gateway || 'банк' }}</div>

  <div class="w-10 h-10 rounded-full flex items-center justify-center mr-3 p-1">
                                <GatewayLogo 
                                    :img_path="data.payment_gateway_logo_path" 
                                    :name="data.payment_gateway" 
                                    class="w-full h-full"
                                />
                            </div>
</div>

            <Card class="bg-white dark:bg-gray-400 border-gray-200 dark:border-gray-700">
                <template #content>
                    <div class="text-gray-900 dark:text-white space-y-3 dark:bg-gray-700">
                        <!-- Заголовок с банком -->
                        <div class="flex items-center">
                            <div class="w-10 h-10 rounded-full flex items-center justify-center mr-3 p-1">
                                <GatewayLogo 
                                    :img_path="data.payment_gateway_logo_path" 
                                    :name="data.payment_gateway" 
                                    class="w-full h-full"
                                />
                            </div>
                            <div>
                                <div class="text-sm text-gray-500 dark:text-gray-300">
                                    <template v-if="data.detail_type === 'phone'">
                                        Перевод в {{ data.payment_gateway || 'банк' }}
                                    </template>
                                    <template v-else-if="data.detail_type === 'card'">
                                        Перевод в {{ data.payment_gateway || 'банк' }}
                                    </template>
                                    <template v-else-if="data.detail_type === 'sim'">
                                        Перевод по сим-карте в {{ data.payment_gateway || 'банк' }}
                                    </template>
                                    <template v-else>
                                        Перевод по счету в {{ data.payment_gateway || 'банк' }}
                                    </template>
                                </div>
                                <div class="text-primary font-medium">
                                    Получатель: {{ data.initials }}
                                </div>
                            </div>
                        </div>
                        
                        <Divider />
                        
                        <!-- Номер/реквизиты -->
                        <div class="flex items-center justify-between">
                            <span class="text-xl font-medium text-gray-900 dark:text-white">{{ formatedPaymentDetail }}</span>
                            <Button 
                                icon="pi pi-copy" 
                                severity="secondary" 
                                text 
                                size="small"
                                @click="copyToClipboard(data.detail)"
                                class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-white"
                            />
                        </div>
                    </div>
                </template>
            </Card>

               <div class="mt-5">
            <Button
                label="Инструкция к оплате"
                icon="pi pi-info-circle"
                @click="openHelperModal"
                class="w-full"
                size="large"
               
            />
        </div>

            <div class="pt-4 text-xl font-medium text-gray-900 dark:text-white ">Точная сумма перевода</div>
            <!-- Сумма перевода -->
            <Card class="bg-white dark:bg-gray-800 border-gray-200 dark:border-gray-700">
                <template #content>
                
                    <div class="text-gray-900 dark:text-white dark:bg-gray-700">
                        
                        <div class="flex items-center justify-between">
                            <span class="text-xl font-medium text-gray-900 dark:text-white">{{ data.amount_formated }}{{ data.currency_symbol }}</span>
                            <Button 
                                icon="pi pi-copy" 
                                severity="secondary" 
                                text 
                                size="small"
                                @click="copyToClipboard(data.amount)"
                                class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-white"
                            />
                        </div>
                    </div>
                </template>
            </Card>
        </div>

        <div v-if="data.detail_type !== 'account_number'" class="mt-3 rounded-lg sm:mt-0 flex items-center p-3 mb-4 sm:text-sm text-sm text-primary border border-primary/30 rounded-xl bg-primary/8">
            <svg class="flex-shrink-0 inline w-4 h-4 me-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                <path d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5ZM9.5 4a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3ZM12 15H8a1 1 0 0 1 0-2h1v-3H8a1 1 0 0 1 0-2h2a1 1 0 0 1 1 1v4h1a1 1 0 0 1 0 2Z"/>
            </svg>
            <div>
                Переводите точную сумму, иначе средства не поступят!
            </div>
        </div>

      
    </div>

</template>

<style scoped>
.dark .p-card {
    background-color: var(--surface-section2)!important;
    color: var(--text-color);
    border: 1px solid var(--surface-border);
}
.p-card {
    background-color: var(--p-menu-item-focus-background)!important;
    color: var(--text-color);
    border: 1px solid var(--surface-border);
}

.rounded-lg {
    border-radius: 12px !important;
}
</style>


