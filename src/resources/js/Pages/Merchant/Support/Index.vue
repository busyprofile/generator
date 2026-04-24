<script setup>
import {Link, Head, router, usePage} from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import MainTableSection from "@/Wrappers/MainTableSection.vue";
import AddMobileIcon from "@/Components/AddMobileIcon.vue";
import DateTime from "@/Components/DateTime.vue";
import EditAction from "@/Components/Table/EditAction.vue";

const supports = usePage().props.supports;

defineOptions({ layout: AuthenticatedLayout })
</script>

<template>
    <div>
        <Head title="Поддержка" />

        <MainTableSection
            title="Управление поддержкой"
            :data="supports"
        >
            <template v-slot:button>
                <button
                    @click="router.visit(route('merchant.support.create'))"
                    type="button"
                    class="hidden md:block text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:ring-blue-300 font-medium rounded-xl text-base px-5 py-2.5 dark:bg-blue-600 dark:hover:bg-blue-700 focus:outline-none dark:focus:ring-blue-800"
                >
                    Добавить саппорта
                </button>
                <AddMobileIcon
                    @click="router.visit(route('merchant.support.create'))"
                />
            </template>
            <template v-slot:body>
                <div class="relative overflow-x-auto shadow-md rounded-table">
                    <table class="w-full text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400">
                        <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                        <tr>
                            <th scope="col" class="px-6 py-3">
                                ID
                            </th>
                            <th scope="col" class="px-6 py-3">
                                Саппорт
                            </th>
                            <th scope="col" class="px-6 py-3">
                                Создан
                            </th>
                            <th scope="col" class="px-6 py-3 flex justify-center">
                                <span class="sr-only">Действия</span>
                            </th>
                        </tr>
                        </thead>
                        <tbody>
                        <tr v-for="support in supports.data" class="bg-white border-b last:border-none dark:bg-gray-800 dark:border-gray-700">
                            <th scope="row" class="px-6 py-3 font-medium whitespace-nowrap text-gray-900 dark:text-gray-200">
                                {{ support.id }}
                            </th>
                            <td class="px-6 py-3 text-nowrap">
                                <div class="inline-flex items-center gap-2">
                                    <!-- <img :src="'https://api.dicebear.com/9.x/'+support.avatar_style+'/svg?seed='+support.avatar_uuid" class="w-10 h-10 rounded-full" alt="support photo"> -->
                                    <div>
                                        <div class="text-nowrap text-gray-900 dark:text-gray-200">
                                            {{ support.email }}
                                        </div>
                                        <div class="text-nowrap text-xs">
                                            {{ support.name }}
                                        </div>
                                    </div>
                                    <span
                                        v-if="support.banned_at"
                                        title="Пользователь заблокирован"
                                    >
                                        <svg class="w-4 h-4 text-red-600" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                                            <path fill-rule="evenodd" d="M2 12C2 6.477 6.477 2 12 2s10 4.477 10 10-4.477 10-10 10S2 17.523 2 12Zm7.707-3.707a1 1 0 0 0-1.414 1.414L10.586 12l-2.293 2.293a1 1 0 1 0 1.414 1.414L12 13.414l2.293 2.293a1 1 0 0 0 1.414-1.414L13.414 12l2.293-2.293a1 1 0 0 0-1.414-1.414L12 10.586 9.707 8.293Z" clip-rule="evenodd"/>
                                        </svg>
                                    </span>
                                </div>
                            </td>
                            <td class="px-6 py-3 text-nowrap">
                                <DateTime :data="support.created_at"/>
                            </td>
                            <td class="px-6 py-3 text-nowrap text-right">
                                <EditAction :link="route('merchant.support.edit', support.id)"></EditAction>
                            </td>
                        </tr>
                        </tbody>
                    </table>
                </div>
            </template>
        </MainTableSection>
    </div>
</template>
