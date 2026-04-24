<script setup>
import {Head} from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PrimeTimeBonus from "@/Pages/Settings/Partials/PrimeTimeBonus.vue";
import SupportLink from "@/Pages/Settings/Partials/SupportLink.vue";
import FundsOnHold from "@/Pages/Settings/Partials/FundsOnHold.vue";
import MaxPendingDisputes from "@/Pages/Settings/Partials/MaxPendingDisputes.vue";
import MaxRejectedDisputes from "@/Pages/Settings/Partials/MaxRejectedDisputes.vue";
import DepositLink from "@/Pages/Settings/Partials/DepositLink.vue";
import PlatformWallet from "@/Pages/Settings/Partials/PlatformWallet.vue";
import MaxConsecutiveFailedOrders from "@/Pages/Settings/Partials/MaxConsecutiveFailedOrders.vue";
import WhitelistedFinanceUserIds from "@/Pages/Settings/Partials/WhitelistedFinanceUserIds.vue";

defineOptions({ layout: AuthenticatedLayout })
</script>

<template>
    <Head title="Настройки системы" />

    <div>
        <div>
            <div class="mx-auto space-y-6">
                <div class="flex justify-between items-center">
                    <h2 class="text-xl text-gray-900 dark:text-white sm:text-4xl">Настройки системы</h2>
                </div>

                <div class="p-5 sm:p-8 bg-white dark:bg-gray-800 shadow rounded-plate ">
                    <PrimeTimeBonus class="max-w-xl"></PrimeTimeBonus>
                </div>
                <div class="p-5 sm:p-8 bg-white dark:bg-gray-800 shadow rounded-plate ">
                    <SupportLink class="max-w-xl"></SupportLink>
                </div>
                <div class="p-5 sm:p-8 bg-white dark:bg-gray-800 shadow rounded-plate ">
                    <DepositLink class="max-w-xl"></DepositLink>
                </div>
                <div class="p-5 sm:p-8 bg-white dark:bg-gray-800 shadow rounded-plate ">
                    <FundsOnHold class="max-w-xl"></FundsOnHold>
                </div>
                <div class="p-5 sm:p-8 bg-white dark:bg-gray-800 shadow rounded-plate ">
                    <MaxPendingDisputes class="max-w-xl"></MaxPendingDisputes>
                </div>
                <div class="p-5 sm:p-8 bg-white dark:bg-gray-800 shadow rounded-plate ">
                    <MaxRejectedDisputes class="max-w-xl"></MaxRejectedDisputes>
                </div>
                <div class="p-5 sm:p-8 bg-white dark:bg-gray-800 shadow rounded-plate ">
                    <MaxConsecutiveFailedOrders class="max-w-xl"></MaxConsecutiveFailedOrders>
                </div>
                <div class="p-5 sm:p-8 bg-white dark:bg-gray-800 shadow rounded-plate ">
                    <PlatformWallet class="max-w-xl"></PlatformWallet>
                </div>
                <div class="p-5 sm:p-8 bg-white dark:bg-gray-800 shadow rounded-plate ">
                    <WhitelistedFinanceUserIds class="max-w-xl"></WhitelistedFinanceUserIds>
                </div>

            </div>
        </div>
    </div>
</template>
