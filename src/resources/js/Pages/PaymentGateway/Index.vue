<script setup>
import {Head, router, usePage} from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import EditAction from "@/Components/Table/EditAction.vue";
import MainTableSection from "@/Wrappers/MainTableSection.vue";
import AddMobileIcon from "@/Components/AddMobileIcon.vue";
import GatewayLogo from "@/Components/GatewayLogo.vue";
import InputFilter from "@/Components/Filters/Pertials/InputFilter.vue";
import FiltersPanel from "@/Components/Filters/FiltersPanel.vue";

import Card from 'primevue/card';
import Button from 'primevue/button';
import Tag from 'primevue/tag';

const payment_gateways = usePage().props.paymentGateways;

defineOptions({ layout: AuthenticatedLayout })
</script>

<template>
    <div>
        <Head title="Платежные методы" />

        <MainTableSection
            title="Платежные методы"
            :data="payment_gateways"
        >
            <template v-slot:button>
                <!-- <Button
                    label="Создать метод"
                    icon="pi pi-plus"
                    class="p-button-sm hidden md:inline-flex"
                    @click="router.visit(route('admin.payment-gateways.create'))"
                /> -->
                <AddMobileIcon
                    @click="router.visit(route('admin.payment-gateways.create'))"
                    class="md:hidden"
                />
            </template>
            <template v-slot:header>
                <FiltersPanel name="payment-gateways">
                    <InputFilter
                        name="search"
                        placeholder="Поиск (название или код)"
                        class="w-full sm:w-64"
                    />
                </FiltersPanel>
            </template>
            <template v-slot:body>
                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
                    <Card v-for="pg in payment_gateways.data" :key="pg.id" class=" duration-200 rounded-lg overflow-hidden">
                        <template #header>
                            <div class="flex justify-between items-center p-3 bg-surface-50 dark:bg-surface-700 rounded-lg">
                                <div class="flex items-center gap-3">
                                    <GatewayLogo :img_path="pg.logo_path" class="w-10 h-10 text-gray-500 dark:text-gray-400 flex-shrink-0"/>
                                    <div>
                                        <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200">{{ pg.name }}</h3>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ pg.code }} <span v-if="pg.nspk_schema">| {{ pg.nspk_schema }}</span></p>
                                    </div>
                                </div>
                             
                            </div>
                        </template>
                        <template #content>
                            <div class="p-4  text-sm flex flex-row justify-between items-center">
                                <div>
                                    <p class="font-medium text-gray-700 dark:text-gray-300">Лимиты для сделок:</p>
                                    <p class="text-gray-600 dark:text-gray-400">Max: {{ pg.max_limit }} {{ pg.currency?.toUpperCase() }}</p>
                                    <p class="text-gray-600 dark:text-gray-400">Min: {{ pg.min_limit }} {{ pg.currency?.toUpperCase() }}</p>
                                </div>
                                <div >
                                    <p class="font-medium text-gray-700 dark:text-gray-300">Комиссия (%):</p>
                                    <p class="text-gray-600 dark:text-gray-400">Вход: {{ pg.trader_commission_rate_for_orders }}% / {{ pg.total_service_commission_rate_for_orders }}%</p>
                                    <p class="text-gray-600 dark:text-gray-400">Выход: {{ pg.trader_commission_rate_for_payouts }}% / {{ pg.total_service_commission_rate_for_payouts }}%</p>
                                </div>
                            </div>
                        </template>
                        <template #footer>
                            <div class="pt-4 border-t border-gray-200 dark:border-gray-600 flex justify-between  ">
                                 <Tag :value="pg.is_active ? 'Активен' : 'Неактивен'" :severity="pg.is_active ? 'success' : 'danger'"  />
                                <Button 
                                    icon="pi pi-pencil" 
                                    class="p-button-sm p-button-text"
                                    @click="router.visit(route('admin.payment-gateways.edit', pg.id))"
                                    v-tooltip.top="'Редактировать'"
                                    label="Редактировать"
                                    size="small"
                                />
                                
                            </div>
                        </template>
                    </Card>
                </div>
                 <div v-if="!payment_gateways.data || payment_gateways.data.length === 0" class="text-center py-12">
                    <i class="pi pi-wallet text-5xl text-gray-400 dark:text-gray-500 mb-3"></i>
                    <p class="text-lg text-gray-500 dark:text-gray-400">Платежные методы не найдены.</p>
                    <p class="text-sm text-gray-400 dark:text-gray-500">Попробуйте изменить фильтры или добавить новый метод.</p>
                </div>
            </template>
        </MainTableSection>
    </div>
</template>

<style scoped>
:deep(.p-card-body) {
    padding: 0; /* Убираем внутренние отступы body у Card, т.к. управляем ими в слотах */
}
:deep(.p-card-content) {
    padding: 0;
}
</style>
