<script setup>
import { Head, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { usePage } from '@inertiajs/vue3';
import MainTableSection from "@/Wrappers/MainTableSection.vue";
import {useViewStore} from "@/store/view.js";
import ShowAction from "@/Components/Table/ShowAction.vue";
import { ref, computed, onMounted, onUnmounted } from "vue";
import Button from 'primevue/button';
import Card from 'primevue/card';
import Tag from 'primevue/tag';

const viewStore = useViewStore();
const merchants = usePage().props.merchants;

const screenWidth = ref(window.innerWidth);
const updateScreenWidth = () => { screenWidth.value = window.innerWidth; };
onMounted(() => { window.addEventListener('resize', updateScreenWidth); });
onUnmounted(() => { window.removeEventListener('resize', updateScreenWidth); });
const buttonSize = computed(() => { return screenWidth.value < 768 ? 'small' : null; });

defineOptions({ layout: AuthenticatedLayout })
</script>

<template>
    <div>
        <Head title="Мерчанты" />

        <MainTableSection
            title="Мерчанты"
            :data="merchants"
        >
            <template v-slot:button>
                <div v-if="viewStore.isMerchantViewMode">
                    <Button
                        label="Создать мерчант"
                        icon="pi pi-plus"
                        @click="router.visit(route('merchants.create'))"
                        :size="buttonSize"
                    />
                </div>
            </template>
            <template v-slot:body>
                <div class="relative overflow-x-auto shadow-md rounded-table " v-if="viewStore.isAdminViewMode">
                    <table class="w-full text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400">
                        <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                            <tr>
                                <th scope="col" class="px-6 py-3">
                                    ID
                                </th>
                                <th scope="col" class="px-6 py-3">
                                    Название
                                </th>
                                <th scope="col" class="px-6 py-3">
                                    Владелец
                                </th>
                                <th scope="col" class="px-6 py-3" v-if="viewStore.isAdminViewMode">
                                    Статус
                                </th>
                                <th scope="col" class="px-6 py-3 flex justify-center">
                                    <span class="sr-only">Действия</span>
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="merchant in merchants.data" class="bg-white border-b last:border-none dark:bg-gray-800 dark:border-gray-700">
                            <th scope="row" class="px-6 py-3 font-medium whitespace-nowrap text-gray-900 dark:text-gray-200">
                                {{ merchant.id }}
                            </th>
                            <td class="px-6 py-3">
                               <div class="text-gray-900 dark:text-gray-200 max-w-48 truncate">{{merchant.name}}</div>
                                <div class="text-xs max-w-36 truncate">{{merchant.domain}}</div>
                            </td>
                            <td class="px-6 py-3">
                                {{merchant.owner.email}}
                            </td>
                            <td class="px-6 py-3">
                                <Tag v-if="!merchant.validated_at" severity="warn" value="На модерации"></Tag>
                                <Tag v-else-if="merchant.banned_at" severity="danger" value="Заблокирован"></Tag>
                                <Tag v-else-if="merchant.active" severity="success" value="Включен"></Tag>
                                <Tag v-else severity="danger" value="Выключен"></Tag>
                            </td>
                            <td class="px-6 py-3 text-right">
                                <ShowAction :link="route('admin.merchants.show', merchant.id)"></ShowAction>
                            </td>
                        </tr>
                        </tbody>
                    </table>
                </div>

                <section v-if="viewStore.isMerchantViewMode" class="antialiased ">
                    <div class="mx-auto">
                        <div class="mb-4 grid gap-4 md:mb-8 grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-3">
                            <Card
                                v-for="(merchant, index) in merchants.data"
                                :key="merchant.id"
                                class="rounded-plate bg-white p-5 sm:p-6 shadow-md dark:bg-gray-800"
                            >
                                <template #title>
                                    <div class="text-lg font-semibold leading-tight text-gray-900 dark:text-gray-200 truncate">
                                        {{ merchant.name }}
                                    </div>
                                </template>
                                <template #subtitle>
                                    <p class="text-sm font-medium text-blue-500 dark:text-blue-500 truncate">
                                        {{ merchant.domain }}
                                    </p>
                                </template>
                                <template #content>
                                    <div class=" flex items-center gap-2">
                                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">доход за сегодня</p>
                                        <p class="text-sm font-medium text-gray-900 dark:text-white">{{ merchant.today_profit }} {{ merchant.profit_currency?.toUpperCase() }}</p>
                                    </div>
                                </template>
                                <template #footer>
                                    <div class="text-sm flex items-center justify-between">
                                        <Tag v-if="!merchant.validated_at" severity="warning" value="На модерации"></Tag>
                                        <Tag v-else-if="merchant.banned_at" severity="danger" value="Заблокирован"></Tag>
                                        <Tag v-else-if="merchant.active" severity="success" value="Включен"></Tag>
                                        <Tag v-else severity="danger" value="Выключен"></Tag>

                                        <Button
                                            label="Перейти"
                                            link
                                            icon="pi pi-arrow-right" iconPos="right"
                                            @click.prevent="router.visit(route('merchants.show', merchant.id))"
                                        />
                                    </div>
                                </template>
                            </Card>
                        </div>
                    </div>
                </section>
            </template>
        </MainTableSection>
    </div>
</template>
<style>
.p-card-subtitle {
    margin-bottom: 0px !important;
}
</style>