<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import {Head, router, useForm, usePage} from "@inertiajs/vue3";
import PaymentDetail from "@/Components/PaymentDetail.vue";
import GatewayLogo from "@/Components/GatewayLogo.vue";
import InputError from "@/Components/InputError.vue";
import GoBackButton from "@/Components/GoBackButton.vue";
import TextArea from "@/Components/TextArea.vue";

const payout = usePage().props.payout;
const formFinishPayout = useForm({})
const formCancelPayout = useForm({
    reason: null,
})
const formPassToTraderPayout = useForm({})

const submitFinishPayout = () => {
    formFinishPayout.post(route('admin.payouts.finish', payout.id), {
        preserveScroll: true
    })
}

const submitCancelPayout = () => {
    formCancelPayout.post(route('admin.payouts.cancel', payout.id), {
        preserveScroll: true
    })
}

const submitPassToTraderPayout = () => {
    formPassToTraderPayout.post(route('admin.payouts.pass-to-trader', payout.id), {
        preserveScroll: true
    })
}

defineOptions({ layout: AuthenticatedLayout })
</script>

<template>
    <div>
        <Head :title="`Выплата #${ payout.id }`" />

       <div class="space-y-6">
           <header>
               <h2 class="text-xl text-gray-900 dark:text-white sm:text-4xl">
                   Выплата #{{ payout.id }}
               </h2>
               <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                   Осуществите выплату или назначьте трейдера.
               </p>
           </header>
           <GoBackButton @click="router.visit(route('admin.payouts.index'))"/>

           <div v-if="$page.props.flash.error" class="flex items-center p-4 mb-6 text-sm text-red-800 border border-red-300 rounded-alert  bg-red-50 dark:bg-gray-800 dark:text-red-400 dark:border-red-800" role="alert">
               <svg class="flex-shrink-0 inline w-4 h-4 me-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                   <path d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5ZM9.5 4a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3ZM12 15H8a1 1 0 0 1 0-2h1v-3H8a1 1 0 0 1 0-2h2a1 1 0 0 1 1 1v4h1a1 1 0 0 1 0 2Z"/>
               </svg>
               <div>
                   <span class="font-medium">Внимание</span> {{ $page.props.flash.error }}
               </div>
           </div>

           <div class="grid grid-cols-2 gap-6">
               <div class="space-y-6">
                   <ul class="text-sm font-medium shadow-md text-gray-900 bg-white rounded-plate dark:bg-gray-800 dark:text-white">
                       <li class="w-full sm:px-6 px-5 py-4 border-b border-gray-200 gap-5 rounded-t-xl dark:border-gray-700 flex items-center justify-between">
                           <span class="text-gray-900 dark:text-gray-200">Сумма</span>
                           <span class="text-gray-500 dark:text-gray-400 truncate break-all">
                                {{ payout.payout_amount }} {{ payout.currency.toUpperCase() }}
                            </span>
                       </li>
                       <li class="w-full sm:px-6 px-5 py-4 border-b border-gray-200 gap-5 rounded-t-xl dark:border-gray-700 flex items-center justify-between">
                           <span class="text-gray-900 dark:text-gray-200">
                                На {{ payout.detail_type.name.toLowerCase() }}
                           </span>
                           <span class="text-gray-500 dark:text-gray-400 truncate break-all">
                               <PaymentDetail :detail="payout.detail" :copyable="false" :type="payout.detail_type.code"></PaymentDetail>
                           </span>
                       </li>
                       <li class="w-full sm:px-6 px-5 py-4 border-b border-gray-200 gap-5 rounded-t-xl dark:border-gray-700 flex items-center justify-between">
                           <span class="text-gray-900 dark:text-gray-200">
                                Получатель
                           </span>
                           <span class="text-gray-500 dark:text-gray-400 truncate break-all">
                                {{ payout.detail_initials }}
                           </span>
                       </li>
                       <li class="w-full sm:px-6 px-5 py-4 border-b border-gray-200 gap-5 rounded-t-xl dark:border-gray-700 flex items-center justify-between">
                           <span class="text-gray-900 dark:text-gray-200">
                                Банк
                           </span>
                           <span class="text-gray-500 dark:text-gray-400 truncate break-all">
                                <div class="flex items-center justify-between gap-3">
                                    <GatewayLogo :img_path="payout.payment_gateway.logo_path" class="w-5 h-5 text-gray-500 dark:text-gray-400"/>
                                    <div>
                                        <div class="text-nowrap">{{ payout.payment_gateway.name }} <span v-if="payout.sub_payment_gateway">({{ payout.sub_payment_gateway.name }})</span></div>
                                    </div>
                                </div>
                           </span>
                       </li>
                       <li class="w-full sm:px-6 px-5 py-4 border-b border-gray-200 gap-5 rounded-t-xl dark:border-gray-700 flex items-center justify-between">
                           <span class="text-gray-900 dark:text-gray-200">
                                Владелец
                           </span>
                           <span class="text-gray-500 dark:text-gray-400 truncate break-all">
                                {{ payout.owner.email }}
                           </span>
                       </li>
                       <li class="w-full sm:px-6 px-5 py-4 border-b border-gray-200 gap-5 rounded-t-xl dark:border-gray-700 flex items-center justify-between">
                           <span class="text-gray-900 dark:text-gray-200">
                                Направление
                           </span>
                           <span class="text-gray-500 dark:text-gray-400 truncate break-all">
                                {{ payout.payout_gateway.name }}
                           </span>
                       </li>
                       <li class="w-full sm:px-6 px-5 py-4 border-b border-gray-200 gap-5 rounded-t-xl dark:border-gray-700 flex items-center justify-between">
                           <span class="text-gray-900 dark:text-gray-200">
                                Трейдер
                           </span>
                           <span class="text-gray-500 dark:text-gray-400 truncate break-all">
                                {{ payout.previous_trader.email }}
                           </span>
                       </li>
                       <li class="w-full sm:px-6 px-5 py-4 gap-5 rounded-t-xl dark:border-gray-700 flex items-center justify-between">
                           <span class="text-gray-900 dark:text-gray-200">
                                Причина отказа
                           </span>
                           <span class="text-gray-500 dark:text-gray-400 w-36 break-words">
                                {{ payout.refuse_reason }}
                           </span>
                       </li>
                   </ul>
               </div>
               <div class="space-y-6">
                   <div class="p-5 sm:p-6 bg-white dark:bg-gray-800 shadow-md rounded-plate w-full">
                       <div class="flex justify-between">
                           <div>
                               <form @submit.prevent="submitFinishPayout" class="w-full">
                                   <div class="text-gray-500 dark:text-gray-400 text-sm mb-3 text-center">
                                       Вы можете самостоятельно обработать выплату, и закрыть ее как выполненную. Средства за после закрытия будут зачислены на ваш счет.
                                   </div>

                                   <div class="mt-4">
                                       <button
                                           type="submit"
                                           :disabled="formFinishPayout.processing"
                                           class="w-full text-white bg-gray-800 hover:bg-gray-900 focus:outline-none focus:ring-4 focus:ring-gray-300 font-medium rounded-xl text-sm px-5 py-2.5 dark:border dark:bg-gray-950/20 dark:text-white dark:border-gray-600 dark:hover:bg-gray-700 dark:hover:border-gray-600 dark:focus:ring-gray-700"
                                       >
                                           Подтвердить перевод
                                       </button>
                                   </div>
                               </form>
                           </div>
                       </div>
                   </div>
                   <div class="p-5 sm:p-6 bg-white dark:bg-gray-800 shadow-md rounded-plate w-full">
                       <div class="flex justify-between">
                           <form @submit.prevent="submitCancelPayout" class="w-full">
                               <div class="text-gray-500 dark:text-gray-400 text-sm mb-3 text-center">
                                    Вы можете отклонить выплату, тогда выплата будет закрыта как не успешная, а средства вернутся на счет мерчанта.
                               </div>

                               <TextArea
                                   id="reason"
                                   class="mt-1 block w-full"
                                   v-model="formCancelPayout.reason"
                                   rows="2"
                                   placeholder="Опишите причину отклонения (не обязательно)."
                                   :error="!!formCancelPayout.errors.reason"
                                   @input="formCancelPayout.clearErrors('reason')"
                               />

                               <InputError class="mt-2" :message="formCancelPayout.errors.reason" />

                               <div class="mt-4">
                                   <button
                                       type="submit"
                                       :disabled="formCancelPayout.processing"
                                       class="w-full focus:outline-none text-white bg-red-700 hover:bg-red-800 focus:ring-4 focus:ring-red-300 font-medium rounded-xl text-sm px-5 py-2.5 dark:bg-red-600 dark:hover:bg-red-700 dark:focus:ring-red-900"
                                   >
                                       Отклонить выплату
                                   </button>
                               </div>
                           </form>
                       </div>
                   </div>
                   <div class="p-5 sm:p-6 bg-white dark:bg-gray-800 shadow-md rounded-plate w-full">
                       <div class="flex justify-between">
                           <div>
                               <form @submit.prevent="submitPassToTraderPayout" class="w-full">
                                   <div class="text-gray-500 dark:text-gray-400 text-sm mb-3 text-center">
                                       Также вы можете передать выплату свободному трейдеру. (будет назначен автоматически)
                                   </div>

                                   <div class="mt-4">
                                       <button
                                           type="submit"
                                           :disabled="formPassToTraderPayout.processing"
                                           class="w-full focus:outline-none text-white bg-green-700 hover:bg-green-800 focus:ring-4 focus:ring-green-300 font-medium rounded-xl text-sm px-5 py-2.5 me-2 mb-2 dark:bg-green-600 dark:hover:bg-green-700 dark:focus:ring-green-800"
                                       >
                                           Передать свободному трейдеру
                                       </button>
                                   </div>
                               </form>
                           </div>
                       </div>
                   </div>
               </div>
           </div>
       </div>
    </div>
</template>

<style scoped>

</style>
