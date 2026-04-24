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
const payout = usePage().props.payout;

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
                   Время на проведение выплаты истекло
               </p>
           </header>
           <GoBackButton @click="router.visit(route('trader.payouts.index'))"/>

           <div class="grid grid-cols-2 gap-6">
               <div class="space-y-6">
                   <div class="p-5 sm:p-6 bg-white dark:bg-gray-800 shadow-md rounded-plate w-full">
                       <div class="text-center">
                           <div>
                               <p class="text-lg text-gray-900 dark:text-gray-200 font-semibold"> Время истекло!</p>
                               <p class="text-gray-500 dark:text-gray-400"> Вы не успели совершить выплату.</p>
                               <p class="text-gray-500 dark:text-gray-400"> Выплата передана администратору.</p>
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
