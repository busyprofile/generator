<script setup>
import {Head, router, useForm, usePage} from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { LoginWidget } from 'vue-tg'
import DateTime from "@/Components/DateTime.vue";
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import {useModalStore} from "@/store/modal.js";
import NotificationModal from "@/Modals/NotificationModal.vue";
import ProgressNumber from "@/Components/ProgressNumber.vue";
import {ref, computed, onMounted, onUnmounted} from "vue";
import {useViewStore} from "@/store/view.js";
import AddMobileIcon from "@/Components/AddMobileIcon.vue";
import Pagination from "@/Components/Pagination/Pagination.vue";
import ConfirmModal from "@/Components/Modals/ConfirmModal.vue";
import Card from 'primevue/card';
import Button from 'primevue/button';

const modalStore = useModalStore();
const viewStore = useViewStore();

const tgBot = ref(usePage().props.tgBot);
const notifications = usePage().props.notifications;

const form = useForm({
    message: '',
});

const openPage = (page) => {
    router.visit(route(route().current()), { data: {
            page
        } })
}

const unlinkTelegram = () => {
    modalStore.openConfirmModal({
        title: 'Отвязка Telegram',
        body: 'Вы уверены, что хотите отвязать Telegram от вашего аккаунта?',
        confirm_button_name: 'Отвязать',
        confirm: () => {
            router.delete(route('notifications.unlink_telegram'));
        }
    });
}

router.on('success', (event) => {
    tgBot.value = usePage().props.tgBot;
})

const currentPage = ref(notifications?.meta?.current_page)

// --- Responsive Button Size Logic ---
const screenWidth = ref(window.innerWidth);
const updateScreenWidth = () => { screenWidth.value = window.innerWidth; };
onMounted(() => { window.addEventListener('resize', updateScreenWidth); });
onUnmounted(() => { window.removeEventListener('resize', updateScreenWidth); });
const buttonSize = computed(() => { return screenWidth.value < 768 ? 'small' : null; });
// --- End Responsive Button Size Logic ---

defineOptions({ layout: AuthenticatedLayout })
</script>

<template>
    <Head title="Уведомления" />

    <div class="mx-auto space-y-6">
        <div class="flex justify-between items-center">
            <h2 class="text-2xl font-bold tracking-tight text-foreground">Уведомления в телеграм</h2>
        </div>

        <div class="grid grid-cols-1 gap-4 xl:grid-cols-3 lg:grid-cols-2 md:grid-cols-2 mb-6">
            <Card class="shadow-md">
                <template #title>
                    <div class="text-xl">Телеграм</div>
                </template>
                <template #content>
                    <template v-if="! tgBot.user_telegram_id">
                        <div class="inline-flex py-3">
                            <div class="text-sm text-foreground">
                                Авторизуйтесь через телеграм, чтобы связать аккаунты.
                            </div>
                        </div>
                        <LoginWidget
                            :bot-username="tgBot.username"
                            :redirect-url="tgBot.redirectUrl"
                        />
                    </template>
                    <template v-else>
                        <div class="inline-flex py-3">
                            <div class="text-sm text-foreground">
                                Для получения уведомлений, и управления аккаунтом через телеграм - <a  :href="tgBot.openTelegramBot" target="_blank" class="text-blue-500 hover:text-blue-600">запустите телеграм бот</a>.
                            </div>
                        </div>
                        <div class="mt-3">
                            <Button
                                label="Отвязать Telegram"
                                @click="unlinkTelegram"
                                severity="danger"
                                :size="buttonSize"
                            />
                        </div>
                    </template>
                </template>
            </Card>
        </div>
        <div v-if="viewStore.isAdminViewMode">
            <div class="flex justify-between items-center mb-3">
                <h2 class="text-xl font-semibold text-foreground">Отправленные уведомления</h2>
                <AddMobileIcon @click="modalStore.openNotificationModal({})" />
            </div>
            <DataTable :value="notifications.data" stripedRows class="w-full mb-3" size="small">
                <Column field="id" header="ID" style="width: 60px" />
                <Column field="message" header="Сообщение" />
                <Column header="Прогресс доставки" style="width: 200px">
                    <template #body="{ data }">
                        <ProgressNumber :current="data.delivered_count" :total="data.recipients_count" />
                    </template>
                </Column>
                <Column header="Дата отправки">
                    <template #body="{ data }">
                        <DateTime class="justify-start text-nowrap" :data="data.created_at" />
                    </template>
                </Column>
            </DataTable>
            <Pagination
                v-model="currentPage"
                :total-items="notifications.meta.total"
                previous-label="Назад" next-label="Вперед"
                @page-changed="openPage"
                :per-page="notifications.meta.per_page"
            />
        </div>
        <NotificationModal/>
        <ConfirmModal/>
    </div>
</template>
