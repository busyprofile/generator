<script setup>
import {Link, Head, router, usePage, useForm} from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import EditAction from "@/Components/Table/EditAction.vue";
import MainTableSection from "@/Wrappers/MainTableSection.vue";
import AddMobileIcon from "@/Components/AddMobileIcon.vue";
import InputFilter from "@/Components/Filters/Pertials/InputFilter.vue";
import FiltersPanel from "@/Components/Filters/FiltersPanel.vue";
import {ref} from "vue";
import FilterCheckbox from "@/Components/Filters/Pertials/FilterCheckbox.vue";
import DateTime from "@/Components/DateTime.vue";
import UserNotesModal from "@/Modals/User/UserNotesModal.vue";
import {useModalStore} from "@/store/modal.js";
import DropdownFilter from "@/Components/Filters/Pertials/DropdownFilter.vue";
import Card from 'primevue/card';
import Chip from 'primevue/chip';
import Button from 'primevue/button';
import Tooltip from 'primevue/tooltip';
import SplitButton from 'primevue/splitbutton';

const users = ref(usePage().props.users);
const modalStore = useModalStore();

const onlineForm = useForm({
    is_online: 0,
    is_payout_online: 0
});

const toggleOnline = (order, type) => {

    onlineForm
        .transform((data) => {
            data.is_online = order.is_online;
            data.is_payout_online = order.is_payout_online;

            if (type === 'order') {
                order.is_online = !order.is_online
                data.is_online = order.is_online;
            } else if (type === 'payout') {
                order.is_payout_online = !order.is_payout_online
                data.is_payout_online = order.is_payout_online;
            }

            return data;
        })
        .patch(route('admin.users.toggle-online', order.id), {
            preserveScroll: true,
            onSuccess: (result) => {
                users.value = result.props.users;
            },
        });
};

const impersonate = (user) => {
    useForm().post(route('admin.impersonate.start', { user: user.id }));
};

const openUserNotesModal = (user) => {
    modalStore.openUserNotesModal({user});
};

const getItems = (user) => {
    let items = [];
    if (user.can_be_impersonated) {
        items.push({
            label: 'Войти как пользователь',
            icon: 'pi pi-sign-in',
            command: () => impersonate(user)
        });
    }
    items.push(
        {
            label: 'Управление финансами',
            icon: 'pi pi-wallet',
            command: () => router.visit(route('admin.users.wallet.index', user.id))
        },
        {
            label: 'Заметки пользователя',
            icon: 'pi pi-file-edit',
            command: () => openUserNotesModal(user)
        }
    );
    return items;
};

defineOptions({ layout: AuthenticatedLayout })
</script>

<template>
    <div>
        <Head title="Пользователи" />

        <UserNotesModal />

        <MainTableSection
            title="Пользователи"
            :data="users"
        >
            <template v-slot:button>
                <!-- <button
                    @click="router.visit(route('admin.users.create'))"
                    type="button"
                    class="hidden md:block text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:ring-blue-300 font-medium rounded-xl text-base px-5 py-2.5 dark:bg-blue-600 dark:hover:bg-blue-700 focus:outline-none dark:focus:ring-blue-800"
                >
                    Создать пользователя
                </button> -->
                <AddMobileIcon
                    @click="router.visit(route('admin.users.create'))"

                />
            </template>

            <template v-slot:table-filters  >
                <FiltersPanel name="users" >
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
                <div class="grid grid-cols-1 md:grid-cols-1 xl:grid-cols-1 gap-4 relative">
                    <template v-if="users && users.data && users.data.length > 0">
                        <Card v-for="user in users.data" :key="user.id" class="user-card h-full  duration-200 overflow-hidden">
                            <template #content>
                                <div class="flex flex-wrap justify-between items-start sm:items-center  gap-x-4 gap-y-3">
                                    <div class="user-detail-group flex items-center gap-3 min-w-[340px] flex-grow sm:flex-grow-0">
                                        <!-- <img :src="'https://api.dicebear.com/9.x/'+user.avatar_style+'/svg?seed='+user.avatar_uuid" class="w-12 h-12 rounded-full flex-shrink-0" alt="user photo"> -->
                                        <div class="flex flex-col">
                                            <div class="flex items-center gap-2 mb-1 flex-wrap">
                                                <span class="text-base font-semibold text-gray-800 dark:text-gray-200">{{ user.email }}</span>
                                                <span class="text-xs text-gray-500 dark:text-gray-400">(ID: {{ user.id }})</span>
                                            </div>
                                            <div class="text-sm text-gray-600 dark:text-gray-300">{{ user.name }}</div>
                                            <div class="mt-1 flex items-center gap-2">
                                                <span
                                                    v-if="user.banned_at"
                                                    title="Пользователь заблокирован"
                                                    class="p-1 bg-red-100 dark:bg-red-700 rounded-full"
                                                >
                                                    <svg class="w-4 h-4 text-red-600 dark:text-red-300" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                                                        <path fill-rule="evenodd" d="M2 12C2 6.477 6.477 2 12 2s10 4.477 10 10-4.477 10-10 10S2 17.523 2 12Zm7.707-3.707a1 1 0 0 0-1.414 1.414L10.586 12l-2.293 2.293a1 1 0 1 0 1.414 1.414L12 13.414l2.293 2.293a1 1 0 0 0 1.414-1.414L13.414 12l2.293-2.293a1 1 0 0 0-1.414-1.414L12 10.586 9.707 8.293Z" clip-rule="evenodd"/>
                                                    </svg>
                                                </span>
                                                <span
                                                    v-if="user.stop_traffic"
                                                    title="Трафик остановлен"
                                                    class="p-1 bg-orange-100 dark:bg-orange-700 rounded-full"
                                                >
                                                    <svg class="w-4 h-4 text-orange-600 dark:text-orange-300" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                                                        <path fill-rule="evenodd" d="M2 12C2 6.477 6.477 2 12 2s10 4.477 10 10-4.477 10-10 10S2 17.523 2 12Zm3-1a1 1 0 0 1 1-1h12a1 1 0 1 1 0 2H6a1 1 0 0 1-1-1Z" clip-rule="evenodd"/>
                                                    </svg>
                                                </span>
                                                <span
                                                    v-else-if="user.traffic_enabled_at"
                                                    :title="'Трафик включен: ' + user.traffic_enabled_at"
                                                     class="p-1 bg-green-100 dark:bg-green-700 rounded-full"
                                                >
                                                    <svg class="w-4 h-4 text-green-600 dark:text-green-300" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                                                        <path fill-rule="evenodd" d="M22 12c0 5.523-4.477 10-10 10S2 17.523 2 12 6.477 2 12 2s10 4.477 10 10Zm-11.99 4a1 1 0 0 1-.705-.292l-3.99-3.96a1 1 0 0 1 1.41-1.419l3.285 3.26 6.289-6.254a1 1 0 0 1 1.41 1.418l-6.99 6.955a1 1 0 0 1-.709.292Z" clip-rule="evenodd"/>
                                                    </svg>
                                                </span>
                                                <span
                                                    v-if="user.is_vip"
                                                    title="VIP пользователь"
                                                    class="p-1 bg-blue-100 dark:bg-blue-700 rounded-full"
                                                >
                                                    <svg class="w-4 h-4 text-blue-500 dark:text-blue-300" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                                                        <path fill-rule="evenodd" d="M10.788 3.103c.495-1.004 1.926-1.004 2.421 0l2.358 4.777 5.273.766c1.107.16 1.55 1.522.748 2.303l-3.816 3.72.9 5.25c.19 1.104-.968 1.945-1.959 1.424l-4.716-2.48-4.715 2.48c-.99.52-2.148-.32-1.96-1.424l.9-5.25-3.815-3.72c-.8-.78-.36-2.142.748-2.303l5.274-.766 2.358-4.777Z" clip-rule="evenodd"/>
                                                    </svg>
                                                </span>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="user-detail-group w-[150px] text-left">
                                        <div class="text-xs text-gray-500 dark:text-gray-400 mb-0.5">Баланс</div>
                                        <div class="text-gray-900 dark:text-gray-100 font-semibold text-lg whitespace-nowrap">
                                            {{ $page.props.auth.can_see_finances ? user.balance : '****' }} $
                                        </div>
                                    </div>

                                    <div class="user-detail-group w-[140px] text-left">
                                        <div class="text-xs text-gray-500 dark:text-gray-400 mb-0.5">Роль</div>
                                        <div class="text-gray-700 dark:text-gray-300 font-medium text-sm whitespace-nowrap">
                                            {{ user.role.name }}
                                        </div>
                                    </div>

                                    <div class="user-detail-group w-[130px] text-left">
                                        <div class="text-xs text-gray-500 dark:text-gray-400 mb-0.5">Пинг</div>
                                        <DateTime v-if="user.apk_latest_ping_at" :data="user.apk_latest_ping_at" :plural="true" class="text-sm text-gray-700 dark:text-gray-300"/>
                                        <span v-else class="text-sm text-gray-500 dark:text-gray-400 italic">Нет данных</span>
                                    </div>

                                    <div class="user-detail-group w-[140px] text-left">
                                        <div class="text-xs text-gray-500 dark:text-gray-400 mb-1">Онлайн</div>
                                        <div class="flex items-center mt-1">
                                            <Button
                                                :label="user.is_online ? 'Включен' : 'Выключен'"
                                                :icon="user.is_online ? 'pi pi-check' : 'pi pi-times'"
                                                :severity="user.is_online ? 'success' : 'danger'"
                                                class="p-button-sm w-full"
                                                @click="toggleOnline(user, 'order')"
                                                :disabled="onlineForm.processing"
                                            />
                                        </div>
                                    </div>

                                    <div class="user-detail-group user-actions w-auto self-center flex items-center justify-end gap-1 ml-auto">
                                        <SplitButton
                                            :model="getItems(user)"
                                            icon="pi pi-pencil"
                                            @click="router.visit(route('admin.users.edit', user.id))"
                                            class="gap-1"
                                            buttonClass="p-button-sm p-button-text  "
                                            menuButtonClass="p-button-sm p-button-text "
                                            aria-label="Действия с пользователем"
                                            v-tooltip.top="'Редактировать'"
                                            label="Редактировать"
                                            size="small"
                                        />
                                    </div>
                                </div>
                            </template>
                        </Card>
                    </template>
                    <div v-else class="text-center py-10 col-span-full">
                        <i class="pi pi-users text-5xl text-gray-400 dark:text-gray-500 mb-3"></i>
                        <p class="text-lg text-gray-500 dark:text-gray-400">Пользователи не найдены.</p>
                        <p class="text-sm text-gray-400 dark:text-gray-500">Попробуйте изменить фильтры.</p>
                    </div>
                </div>
            </template>
        </MainTableSection>
    </div>
</template>

<style scoped>
.user-card .p-card-content {
    padding: 0 !important;
}
:deep(.p-card) {
    background-color: var(--surface-card);
    border-radius: 0.5rem; /* PrimeVue default is 6px, Tailwind uses 0.5rem for rounded-lg */
    transition: all 0.2s;
    border: 1px solid var(--surface-border); /* Ensure consistent border */
}



/* Remove padding from header, body, and content of the card if not needed or handled by inner elements */
:deep(.user-card .p-card-header) {
    padding: 0;
}

:deep(.user-card .p-card-body) {
    padding: 0; /* This will remove the default padding around the content slot */
}

:deep(.user-card .p-card-content) {
    padding: 0; /* Ensure this is also zero if p-card-body padding is removed */
}

/* Ensure PrimeVue buttons used as icon buttons are nicely sized and aligned */
:deep(.p-button.p-button-icon-only) {
    width: 2.5rem; /* Adjust as needed */
    height: 2.5rem; /* Adjust as needed */
}

/* Adjust SplitButton styles to match the screenshot */
:deep(.p-splitbutton) {
    /*gap: 0.2rem; !* Reduce gap between buttons *!*/
}

:deep(.p-splitbutton .p-button:first-child) { /* Main action button (Edit) */
    border-top-right-radius: 0;
    border-bottom-right-radius: 0;
    /*padding: 0.5rem 0.75rem; !* Adjust padding if needed *!*/
}

:deep(.p-splitbutton .p-button:last-child) { /* Menu button (Dropdown arrow) */
    border-top-left-radius: 0;
    border-bottom-left-radius: 0;
   /* padding: 0.5rem 0.5rem; !* Adjust padding for the arrow button *!*/
}

:deep(.p-splitbutton .p-button-icon-only .pi) {
  /* font-size: 1rem; !* Adjust icon size if needed *!*/
}

/* Custom styling for menu items if needed */
:deep(.p-menuitem-link) {
    /* padding: 0.75rem 1rem; */
}

:deep(.p-menuitem-icon) {
    /* margin-right: 0.5rem; */
}
</style>
