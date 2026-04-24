<script setup>
import {Head, usePage, useForm} from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import MainTableSection from "@/Wrappers/MainTableSection.vue";
import InputFilter from "@/Components/Filters/Pertials/InputFilter.vue";
import FiltersPanel from "@/Components/Filters/FiltersPanel.vue";
import {ref} from "vue";
import FilterCheckbox from "@/Components/Filters/Pertials/FilterCheckbox.vue";
import DateTime from "@/Components/DateTime.vue";
import DropdownFilter from "@/Components/Filters/Pertials/DropdownFilter.vue";
const users = ref(usePage().props.users);

const form = useForm({});

const toggleTraffic = (user) => {
    form.patch(route('support.users.toggle-traffic', user.id), {
        preserveScroll: true,
        onSuccess: (result) => {
            users.value = result.props.users;
        },
    });
};

defineOptions({ layout: AuthenticatedLayout })
</script>

<template>
    <div>
        <Head title="Пользователи" />

        <MainTableSection
            title="Пользователи"
            :data="users"
        >
            <template v-slot:header>
                <FiltersPanel name="users">
                    <InputFilter
                        name="user"
                        placeholder="Поиск (почта или имя)"
                        class="w-64"
                    />
                    <DropdownFilter
                        name="roles"
                        title="Роли"
                    />
                    <FilterCheckbox
                        name="online"
                        title="Онлайн"
                    />
                    <FilterCheckbox
                        name="traffic_disabled"
                        title="Трафик выключен"
                    />
                </FiltersPanel>
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
                                Пользователь
                            </th>
                            <th scope="col" class="px-6 py-3">
                                Баланс
                            </th>
                            <th scope="col" class="px-6 py-3">
                                Роль
                            </th>
                            <th scope="col" class="px-6 py-3">
                                Пинг
                            </th>
                            <th scope="col" class="px-6 py-3">
                                Создан
                            </th>
                            <th scope="col" class="px-6 py-3">
                                Онлайн
                            </th>
                            <th scope="col" class="px-6 py-3">
                                Трафик
                            </th>
                        </tr>
                        </thead>
                        <tbody>
                        <tr v-for="user in users.data" class="bg-white border-b last:border-none dark:bg-gray-800 dark:border-gray-700">
                            <th scope="row" class="px-6 py-3 font-medium whitespace-nowrap text-gray-900 dark:text-gray-200">
                                {{ user.id }}
                            </th>
                            <td class="px-6 py-3 text-nowrap">
                                <div class="inline-flex items-center gap-2">
                                    <!-- <img :src="'https://api.dicebear.com/9.x/'+user.avatar_style+'/svg?seed='+user.avatar_uuid" class="w-10 h-10 rounded-full" alt="user photo"> -->
                                    <div>
                                        <div class="text-nowrap text-gray-900 dark:text-gray-200">
                                            {{ user.email }}
                                        </div>
                                        <div class="text-nowrap text-xs">
                                            {{ user.name }}
                                        </div>
                                    </div>
                                    <span
                                        v-if="user.banned_at"
                                        title="Пользователь заблокирован"
                                    >
                                        <svg class="w-4 h-4 text-red-600" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                                            <path fill-rule="evenodd" d="M2 12C2 6.477 6.477 2 12 2s10 4.477 10 10-4.477 10-10 10S2 17.523 2 12Zm7.707-3.707a1 1 0 0 0-1.414 1.414L10.586 12l-2.293 2.293a1 1 0 1 0 1.414 1.414L12 13.414l2.293 2.293a1 1 0 0 0 1.414-1.414L13.414 12l2.293-2.293a1 1 0 0 0-1.414-1.414L12 10.586 9.707 8.293Z" clip-rule="evenodd"/>
                                        </svg>
                                    </span>
                                    <span
                                        v-if="user.stop_traffic"
                                        title="Трафик остановлен"
                                    >
                                        <svg class="w-4 h-4 text-red-600" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                                            <path fill-rule="evenodd" d="M2 12C2 6.477 6.477 2 12 2s10 4.477 10 10-4.477 10-10 10S2 17.523 2 12Zm3-1a1 1 0 0 1 1-1h12a1 1 0 1 1 0 2H6a1 1 0 0 1-1-1Z" clip-rule="evenodd"/>
                                        </svg>
                                    </span>
                                </div>
                            </td>
                            <td class="px-6 py-3 text-nowrap">
                                {{ $page.props.auth.can_see_finances ? user.balance : '****' }} $
                            </td>
                            <td class="px-6 py-3 text-nowrap">
                                {{ user.role.name }}
                            </td>
                            <td class="px-6 py-3 text-nowrap">
                                <DateTime v-if="user.apk_latest_ping_at" :data="user.apk_latest_ping_at" :plural="true"/>
                            </td>
                            <td class="px-6 py-3 text-nowrap">
                                {{ user.created_at }}
                            </td>
                            <td class="px-6 py-3 text-nowrap">
                                <div class="flex items-center">
                                    <div class="mr-2 w-3 h-3 rounded-full" :class="user.is_online ? 'bg-green-500' : 'bg-red-500'"></div>
                                    <span :class="user.is_online ? 'text-sm font-medium text-green-500 dark:text-green-400' : 'text-sm font-medium text-red-500 dark:text-red-500'">
                                        {{ user.is_online ? 'Онлайн' : 'Офлайн' }}
                                    </span>
                                </div>
                            </td>
                            <td class="px-6 py-3 text-nowrap">
                                <label class="inline-flex items-center cursor-pointer">
                                    <input
                                        type="checkbox"
                                        class="sr-only peer"
                                        :checked="!user.stop_traffic"
                                        @change="toggleTraffic(user)"
                                        :disabled="form.processing"
                                    >
                                    <div class="relative w-9 h-5 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-green-300 dark:peer-focus:ring-green-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:w-4 after:h-4 after:transition-all dark:border-gray-600 peer-checked:bg-green-600"></div>
                                    <span class="ms-2 text-xs font-medium text-gray-900 dark:text-gray-300">
                                        {{ user.stop_traffic ? 'Выкл.' : 'Вкл.' }}
                                    </span>
                                </label>
                            </td>
                        </tr>
                        </tbody>
                    </table>
                </div>
            </template>
        </MainTableSection>
    </div>
</template>
