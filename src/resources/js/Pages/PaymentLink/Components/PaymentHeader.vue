<script setup>
const props = defineProps({
    stage: {
        type: String,
    },
    data: {
        type: Object,
        default: {}
    }
});
</script>





<template>


       

                
    <div v-show="stage !== 'select_gateway'" class="sm:mx-0 mx-2       ">
        <div class="flex justify-between mt-3 w-full px-6 py-5 text-sm text-gray-800 bg-white dark:bg-gray-800 dark:text-gray-300 border border-gray-200 dark:border-gray-700 rounded-lg ">
            <div>
                <div class="text-gray-900 dark:text-gray-200 text-2xl">{{ data.amount_formated }}{{ data.currency_symbol }}</div>
                <div class="text-gray-400 dark:text-gray-500">Сумма для оплаты</div>
            </div>
            <div v-show="stage === 'payment'">
                <div class="text-gray-900 dark:text-gray-200 text-2xl">
                    <slot name="clock"></slot>
                </div>
                <div class="text-gray-400 dark:text-gray-500">Время на оплату</div>
            </div>
        </div>
       
    </div>
</template>

<style scoped>
 .rounded-lg {
    border-radius: 12px !important;
}
</style>
