<script setup>
import { computed } from 'vue';
import Card from 'primevue/card';
import Tag from 'primevue/tag';

const props = defineProps({
    statistics: {
        type: Object,
        default: () => ({}),
    },
});

const stats = computed(() => props.statistics ?? {});
</script>

<template>
    <div>
        <section>
            <Card class="rounded-plate overflow-hidden">
                <template #content>
                    <div class="grid max-w-full text-gray-900 xl:grid-cols-4 md:grid-cols-2 sm:grid-cols-1 dark:text-white">
                        <div class="xl:border-b-0 border-b md:border-r border-gray-200 dark:border-gray-700 py-5 flex flex-col items-center justify-center text-center">
                            <div class="mb-2 text-3xl md:text-3xl font-extrabold">
                                {{ $page.props.auth.can_see_finances ? stats.today_turnover : '****' }}
                                <span class="text-xl text-gray-500 dark:text-gray-400">
                                    {{ stats.currency.toUpperCase() }}
                                </span>
                            </div>
                            <div class="font-light text-gray-500 dark:text-gray-400">Оборот за сегодня</div>
                            <div class="mt-4 text-sm text-gray-500 dark:text-gray-400">
                                Платежей {{ stats.today_orders_count }}
                            </div>
                        </div>

                        <div class="xl:border-b-0 border-b xl:border-r border-gray-200 dark:border-gray-700 py-5 flex flex-col items-center justify-center text-center">
                            <div class="mb-2 text-3xl md:text-3xl font-extrabold">
                                {{ $page.props.auth.can_see_finances ? stats.yesterday_turnover : '****' }}
                                <span class="text-xl text-gray-500 dark:text-gray-400">
                                    {{ stats.currency.toUpperCase() }}
                                </span>
                            </div>
                            <div class="font-light text-gray-500 dark:text-gray-400">Вчера</div>
                            <div class="mt-4 text-sm text-gray-500 dark:text-gray-400">
                                Платежей {{ stats.yesterday_orders_count }}
                            </div>
                        </div>

                        <div class="border-b md:border-b-0 md:border-r border-gray-200 dark:border-gray-700 py-5 flex flex-col items-center justify-center text-center">
                            <div class="mb-2 text-3xl md:text-3xl font-extrabold">
                                {{ $page.props.auth.can_see_finances ? stats.month_turnover : '****' }}
                                <span class="text-xl text-gray-500 dark:text-gray-400">
                                    {{ stats.currency.toUpperCase() }}
                                </span>
                            </div>
                            <div class="font-light text-gray-500 dark:text-gray-400">За 30 дней</div>
                            <div class="mt-4 text-sm text-gray-500 dark:text-gray-400">
                                Платежей {{ stats.month_orders_count }}
                            </div>
                        </div>

                        <div class="py-5 flex flex-col items-center justify-center text-center">
                            <div class="mb-2 text-3xl md:text-3xl font-extrabold">
                                {{ $page.props.auth.can_see_finances ? stats.total_turnover : '****' }}
                                <span class="text-xl text-gray-500 dark:text-gray-400">
                                    {{ stats.currency.toUpperCase() }}
                                </span>
                            </div>
                            <div class="font-light text-gray-500 dark:text-gray-400">Оборот за все время</div>
                            <Tag class="mt-1" icon="pi pi-check-circle" severity="info">
                                Платежей {{ stats.total_orders_count }}
                            </Tag>
                        </div>
                    </div>
                </template>
            </Card>
        </section>
    </div>
</template>
