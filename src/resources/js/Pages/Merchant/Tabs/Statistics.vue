<script setup>
import {usePage} from "@inertiajs/vue3";
import Card from 'primevue/card';
import Tag from 'primevue/tag';

const statistics = usePage().props.statistics;
</script>

<template>
    <div>
        <section>
            <Card class="rounded-plate overflow-hidden">
                <template #content>
                    <div class="grid max-w-full text-gray-900 xl:grid-cols-4 md:grid-cols-2 sm:grid-cols-1 dark:text-white">
                        <div class="xl:border-b-0 border-b md:border-r border-gray-200 dark:border-gray-700 py-5 flex flex-col items-center justify-center text-center">
                            <div class="mb-2 text-3xl md:text-3xl font-extrabold">
                                {{ $page.props.auth.can_see_finances ? statistics.today_profit : '****' }}
                                <span class="text-xl text-gray-500 dark:text-gray-400">
                                    {{ statistics.currency.toUpperCase() }}
                                </span>
                            </div>
                            <div class="font-light text-gray-500 dark:text-gray-400">Доход за сегодня</div>
                            <Tag class="mt-1" icon="pi pi-check-circle" severity="info">
                                Платежей {{ statistics.today_orders_count }}
                            </Tag>
                        </div>
                        <div class="xl:border-b-0 border-b lg:border-r border-gray-200 dark:border-gray-700 py-5 flex flex-col items-center justify-center text-center">
                            <div class="mb-2 text-3xl md:text-3xl font-extrabold">
                                {{ $page.props.auth.can_see_finances ? statistics.yesterday_profit : '****' }}
                                <span class="text-xl text-gray-500 dark:text-gray-400">
                                    {{ statistics.currency.toUpperCase() }}
                                </span>
                            </div>
                            <div class="font-light text-gray-500 dark:text-gray-400">Доход за вчера</div>
                            <Tag class="mt-1" icon="pi pi-check-circle" severity="info">
                                Платежей {{ statistics.yesterday_orders_count }}
                            </Tag>
                        </div>
                        <div class="md:border-b-0 border-b md:border-r border-gray-200 dark:border-gray-700 py-5 flex flex-col items-center justify-center text-center">
                            <div class="mb-2 text-3xl md:text-3xl font-extrabold">
                                {{ $page.props.auth.can_see_finances ? statistics.month_profit : '****' }}
                                <span class="text-xl text-gray-500 dark:text-gray-400">
                                    {{ statistics.currency.toUpperCase() }}
                                </span>
                            </div>
                            <div class="font-light text-gray-500 dark:text-gray-400">Доход за месяц</div>
                            <Tag class="mt-1" icon="pi pi-check-circle" severity="info">
                                Платежей {{ statistics.month_orders_count }}
                            </Tag>
                        </div>
                        <div class="flex flex-col items-center justify-center py-5 text-center">
                            <div class="mb-2 text-3xl md:text-3xl font-extrabold">
                                {{ $page.props.auth.can_see_finances ? statistics.total_profit : '****' }}
                                <span class="text-xl text-gray-500 dark:text-gray-400">
                                    {{ statistics.currency.toUpperCase() }}
                                </span>
                            </div>
                            <div class="font-light text-gray-500 dark:text-gray-400">Доход за все время</div>
                            <Tag class="mt-1" icon="pi pi-check-circle" severity="info">
                                Платежей {{ statistics.total_orders_count }}
                            </Tag>
                        </div>
                    </div>
                </template>
            </Card>
        </section>
    </div>
</template>

<style scoped>

</style>
