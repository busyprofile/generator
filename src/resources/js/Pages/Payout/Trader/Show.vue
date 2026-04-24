<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import {Head, Link, router, useForm, usePage} from "@inertiajs/vue3";
import Clock from "@/Components/Clock.vue";
import {nextTick, onMounted, ref} from "vue";
import PaymentDetail from "@/Components/PaymentDetail.vue";
import GatewayLogo from "@/Components/GatewayLogo.vue";
import InputError from "@/Components/InputError.vue";
import Dropzone from "@/Components/Form/Dropzone.vue";
import GoBackButton from "@/Components/GoBackButton.vue";
import TextArea from "@/Components/TextArea.vue";
import {initFlowbite} from "flowbite";

const payout = ref(usePage().props.payout);
const clockRef = ref(null);

const formFinishPayout = useForm({
    video_receipt: null,
})
const formRefusePayout = useForm({
    reason: null,
})

const initializeClock = () => {
    nextTick(() => {
        clockRef.value.initializeClock();
    });
}

onMounted(() => {
    initializeClock();

    setInterval(async () => {
        router.reload({
            only: ['payout'],
            onFinish: () => {
                payout.value = usePage().props.payout;
            }
        })
    }, 3000);
})

const submitFinishPayout = () => {
    formFinishPayout.post(route('trader.payouts.finish', payout.value.id))
}

const submitRefusePayout = () => {
    formRefusePayout.post(route('trader.payouts.refuse', payout.value.id))
}

const timerIsExpired = () => {
    //window.location.reload();
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
                   Осуществите выплату до истечения таймера.
               </p>
           </header>
           <GoBackButton @click="router.visit(route('trader.payouts.index'))"/>

           <div class="grid grid-cols-2 gap-6">
               <div class="space-y-6">
                   <div class="p-5 sm:p-6 bg-white dark:bg-gray-800 shadow-md rounded-plate w-full">
                       <div class="flex justify-between">
                           <div>
                               <div class="text-gray-900 dark:text-gray-200 text-2xl">
                                   <Clock :expires_at="payout.expires_at" :now="payout.now" @expired="timerIsExpired" ref="clockRef"/>
                               </div>
                               <div class="text-gray-400 dark:text-gray-500">Время на оплату</div>
                           </div>
                       </div>
                   </div>
                   <ul class="text-sm font-medium shadow-md text-gray-900 bg-white rounded-plate dark:bg-gray-800 dark:text-white">
                       <li class="w-full sm:px-6 px-5 py-4 border-b border-gray-200 gap-5 rounded-t-xl dark:border-gray-700 flex justify-between">
                           <span class="text-gray-900 dark:text-gray-200">Сумма</span>
                           <span class="text-gray-500 dark:text-gray-400 truncate break-all">
                                {{ payout.payout_amount }} {{ payout.currency.toUpperCase() }}
                            </span>
                       </li>
                       <li class="w-full sm:px-6 px-5 py-4 border-b border-gray-200 gap-5 rounded-t-xl dark:border-gray-700 flex justify-between">
                           <span class="text-gray-900 dark:text-gray-200">
                                На {{ payout.detail_type.name.toLowerCase() }}
                           </span>
                           <span class="text-gray-500 dark:text-gray-400 truncate break-all">
                               <PaymentDetail :detail="payout.detail" :copyable="false" :type="payout.detail_type.code"></PaymentDetail>
                           </span>
                       </li>
                       <li class="w-full sm:px-6 px-5 py-4 border-b border-gray-200 gap-5 rounded-t-xl dark:border-gray-700 flex justify-between">
                           <span class="text-gray-900 dark:text-gray-200">
                                Получатель
                           </span>
                           <span class="text-gray-500 dark:text-gray-400 truncate break-all">
                                {{ payout.detail_initials }}
                           </span>
                       </li>
                       <li class="w-full sm:px-6 px-5 py-4 gap-5 rounded-t-xl dark:border-gray-700 flex justify-between">
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
                   </ul>
               </div>
               <div class="space-y-6">
                   <div class="p-5 sm:p-6 bg-white dark:bg-gray-800 shadow-md rounded-plate w-full">
                       <div class="flex justify-between">
                           <div>
                               <form @submit.prevent="submitFinishPayout" class="w-full">
                                   <div class="text-gray-500 dark:text-gray-400 text-sm mb-3 text-center">
                                       Загрузите видео подтверждение перевода, чтобы мы могли подтвердить платеж.
                                   </div>
                                   <Dropzone v-model="formFinishPayout.video_receipt" description="Расширение: mov, mp4"/>
                                   <InputError :message="formFinishPayout.errors.video_receipt" class="mt-2" />

                                   <div class="mt-4">
                                       <button
                                           type="submit"
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
                           <div>
                               <form @submit.prevent="submitRefusePayout" class="w-full">
                                   <div class="text-gray-500 dark:text-gray-400 text-sm mb-3 text-center">
                                       Если вы не можете осуществить перевод, по каким-то причинам, то вы можете отказаться от выплаты. Но так делать не желательно :)
                                   </div>

                                   <TextArea
                                       id="reason"
                                       class="mt-1 block w-full"
                                       v-model="formRefusePayout.reason"
                                       required
                                       :rows="2"
                                       placeholder="Опишите причину отказа."
                                       :error="!!formRefusePayout.errors.reason"
                                       @input="formRefusePayout.clearErrors('reason')"
                                   />

                                   <InputError class="mt-2" :message="formRefusePayout.errors.reason" />

                                   <div class="mt-4">
                                       <button
                                           type="submit"
                                           class="w-full focus:outline-none text-white bg-red-700 hover:bg-red-800 focus:ring-4 focus:ring-red-300 font-medium rounded-xl text-sm px-5 py-2.5 dark:bg-red-600 dark:hover:bg-red-700 dark:focus:ring-red-900"
                                       >
                                           Отказаться от выплаты
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
