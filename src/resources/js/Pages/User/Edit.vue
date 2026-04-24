<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import {Head, useForm, usePage, router} from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import TextInput from "@/Components/TextInput.vue";
import NumberInput from "@/Components/NumberInput.vue";
import Select from "@/Components/Select.vue";
import SecondaryPageSection from "@/Wrappers/SecondaryPageSection.vue";
import ConfirmModal from "@/Components/Modals/ConfirmModal.vue";
import {useModalStore} from "@/store/modal.js";
import {ref, computed, watch} from "vue";
import DateTime from "@/Components/DateTime.vue";
import Multiselect from "@/Components/Form/Multiselect.vue";
import Button from 'primevue/button';
import TraderTeamLeaders from '@/Components/TraderTeamLeaders.vue';
import Tooltip from 'primevue/tooltip';
import axios from 'axios';

import InputText from 'primevue/inputtext';
import Dropdown from 'primevue/dropdown';
import InputNumber from 'primevue/inputnumber';
import MultiSelect from 'primevue/multiselect';
import OverlayPanel from 'primevue/overlaypanel';

const modalStore = useModalStore();
const props = usePage().props;
const user = ref(props.user);
const roles = props.roles;
const teamLeadersList = props.teamLeadersList || [];
const traderTeamLeaders = props.traderTeamLeaders || [];
const merchantTeamLeaders = props.merchantTeamLeaders || [];
const traderCategories = props.traderCategories || [];

// Refs для overlay panels
const traderCommissionOP = ref();
const vipStatusOP = ref();
const trafficStatusOP = ref();

// Создаем форматированный список тимлидеров для селекта
const formattedTeamLeadersList = computed(() => {
    return teamLeadersList.map(tl => ({
        ...tl,
        // Формируем новую метку: Имя (Процент%)
        formatted_label: `${tl.name || tl.email} (${tl.referral_commission_percentage || 0}%)`
    }));
});

// Обрабатываем данные тимлидеров для формы
const formattedTeamLeaders = computed(() => {
    if (!traderTeamLeaders || !Array.isArray(traderTeamLeaders) || traderTeamLeaders.length === 0) {
        return [];
    }
    
    console.log('Форматирование тимлидеров:', traderTeamLeaders);
    
    // Убедимся что для каждого тимлидера есть все необходимые поля
    return traderTeamLeaders.map(tl => ({
        team_leader_id: tl.team_leader_id.toString(), // Convert to string for select
        commission_percentage: parseFloat(tl.commission_percentage || 0),
        is_primary: Boolean(tl.is_primary)
    }));
});

// Обрабатываем значение trader_commission_rate - преобразуем null в пустую строку для правильного отображения
const formattedTraderCommissionRate = user.value.trader_commission_rate === null ? '' : user.value.trader_commission_rate;

const form = useForm({
    name: user.value.name,
    email: user.value.email,
    role_id: user.value.role.id,
    trader_category_id: user.value.trader_category_id || '',
    banned: user.value.banned_at ? true : false,
    payouts_enabled: user.value.payouts_enabled ? true : false,
    stop_traffic: user.value.stop_traffic ? true : false,
    is_vip: user.value.is_vip ? true : false,
    referral_commission_percentage: user.value.referral_commission_percentage || 0,
    trader_commission_rate: formattedTraderCommissionRate,
    promo_code: '',
    additional_team_leader_ids: user.value.additional_team_leader_ids || [],
    trader_team_leaders: formattedTeamLeaders.value,
    merchant_team_leaders: merchantTeamLeaders,
});

// Проверка, является ли пользователь админом (role_id === 1)
const isAdmin = (roleId) => roleId === 1;
const isTrader = (roleId) => roleId === 2;
// Проверка, является ли пользователь мерчантом (role_id === 3)
const isMerchant = (roleId) => roleId === 3;
// Проверка, является ли пользователь Team Leader (role_id === 5)
const isTeamLeader = (roleId) => roleId === 5;
// Проверка, имеет ли пользователь доступ к функционалу выплат
const hasPayoutsAccess = (roleId) => isTrader(roleId) || isMerchant(roleId) || isAdmin(roleId);

const submit = () => {
    // Преобразуем пустую строку или "0" в null для trader_commission_rate
    if (form.trader_commission_rate === '' || form.trader_commission_rate === '0' || form.trader_commission_rate === 0) {
        form.trader_commission_rate = null;
    }
    
    // Подготавливаем данные тимлидеров трейдера к отправке
    if (form.trader_team_leaders && Array.isArray(form.trader_team_leaders) && form.trader_team_leaders.length > 0) {
        form.trader_team_leaders = form.trader_team_leaders.map(tl => ({
            team_leader_id: tl.team_leader_id,
            commission_percentage: parseFloat(tl.commission_percentage || 0),
            is_primary: Boolean(tl.is_primary)
        }));
        
        const primaryCount = form.trader_team_leaders.filter(tl => tl.is_primary).length;
        if (primaryCount === 0 && form.trader_team_leaders.length > 0) {
            form.trader_team_leaders[0].is_primary = true;
        }
    }

    // Подготавливаем данные тимлидеров мерчанта к отправке
    if (form.merchant_team_leaders && Array.isArray(form.merchant_team_leaders) && form.merchant_team_leaders.length > 0) {
        form.merchant_team_leaders = form.merchant_team_leaders.map(tl => ({
            team_leader_id: tl.team_leader_id,
            commission_percentage: parseFloat(tl.commission_percentage || 0),
            is_primary: Boolean(tl.is_primary)
        }));
        
        const primaryCount = form.merchant_team_leaders.filter(tl => tl.is_primary).length;
        if (primaryCount === 0 && form.merchant_team_leaders.length > 0) {
            form.merchant_team_leaders[0].is_primary = true;
        }
    }
    
    console.log('Отправка формы с данными:', {
        trader_team_leaders: form.trader_team_leaders ? JSON.stringify(form.trader_team_leaders) : '[]',
        merchant_team_leaders: form.merchant_team_leaders ? JSON.stringify(form.merchant_team_leaders) : '[]'
    });
    
    form.patch(route('admin.users.update', user.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            router.reload({ only: ['user', 'traderTeamLeaders', 'merchantTeamLeaders'] });
        },
        onError: (errors) => {
            console.error('Ошибки при сохранении:', errors);
        }
    });
};

const reset2fa = () => {
    modalStore.openConfirmModal({
        title: 'Сброс 2FA',
        body: 'Вы уверены, что хотите сбросить двухфакторную аутентификацию для этого пользователя?',
        confirm_button_name: 'Сбросить',
        confirm: () => {
            router.delete(route('admin.users.reset-2fa', user.value.id));
        }
    });
};

router.on('success', (event) => {
    user.value = usePage().props.user;
})

defineOptions({ layout: AuthenticatedLayout })
</script>

<template>
    <div>
        <Head title="Редактирование пользователя" />

        <SecondaryPageSection
            :back-link="route('admin.users.index')"
            :title="'Редактирование пользователя - ' + user.email"
            description="Здесь вы можете отредактировать пользователя."
        >
            <form @submit.prevent="submit" class="mt-2">
                <div class="md:grid md:grid-cols-2 md:gap-x-8 md:gap-y-6">
                    <div>
                        <label for="name" class="block mt-3 text-sm font-medium text-foreground">Имя</label>
                        <InputText
                            id="name"
                            class="mt-1 block w-full"
                            v-model="form.name"
                            required
                            autofocus
                            autocomplete="name"
                            :invalid="!!form.errors.name"
                            @input="form.clearErrors('name')"
                        />
                        <InputError class="mt-2" :message="form.errors.name" />
                    </div>

                    <div>
                        <label for="email" class="block mt-3 text-sm font-medium text-foreground">Почта</label>
                        <InputText
                            id="email"
                            type="email"
                            class="mt-1 block w-full"
                            v-model="form.email"
                            required
                            autocomplete="username"
                            :invalid="!!form.errors.email"
                            @input="form.clearErrors('email')"
                        />
                        <InputError class="mt-2" :message="form.errors.email" />
                    </div>

                    <div v-if="user.id !== 1">
                        <label for="roles" class="block mt-3 text-sm font-medium text-foreground">Роль</label>
                        <Dropdown
                            id="roles"
                            v-model="form.role_id"
                            :options="roles"
                            optionLabel="name"
                            optionValue="id"
                            placeholder="Выберите роль"
                            class="w-full mt-1"
                            :invalid="!!form.errors.role_id"
                            @change="form.clearErrors('role_id')"
                        />
                        <InputError class="mt-2" :message="form.errors.role_id" />
                    </div>

                    <!-- Поле выбора категории трейдера -->
                    <div v-if="isTrader(form.role_id)">
                        <label for="trader_category_id" class="block mt-3 text-sm font-medium text-foreground">
                            Категория трейдера
                        </label>
                        <Dropdown
                            id="trader_category_id"
                            v-model="form.trader_category_id"
                            :options="traderCategories"
                            optionLabel="name"
                            optionValue="id"
                            placeholder="Выберите категорию (опционально)"
                            class="w-full mt-1"
                            :invalid="!!form.errors.trader_category_id"
                            @change="form.clearErrors('trader_category_id')"
                            showClear
                        />
                        <InputError class="mt-2" :message="form.errors.trader_category_id" />
                        <div class="mt-1 text-sm text-muted-foreground">
                            Категория влияет на приоритет назначения трейдера на заказы. Можно оставить пустым.
                        </div>
                    </div>

                    <div v-if="isTrader(form.role_id)">
                        <div class="flex items-center mt-3">
                            <label for="trader_commission_rate" class="block text-sm font-medium text-foreground">
                                Индивидуальная комиссия трейдера (%)
                            </label>
                            <i class="pi pi-info-circle ml-2 cursor-pointer text-muted-foreground" 
                               @click="traderCommissionOP.toggle($event)"></i>
                            
                            <OverlayPanel ref="traderCommissionOP">
                                <div class="p-3 text-sm">
                                    <p>Если не указано, используется комиссия из платежного шлюза.</p>
                                </div>
                            </OverlayPanel>
                        </div>
                        <InputNumber
                            id="trader_commission_rate"
                            class="w-full mt-1"
                            v-model="form.trader_commission_rate"
                            :invalid="!!form.errors.trader_commission_rate"
                             @input="(event) => {
                                form.clearErrors('trader_commission_rate');
                                if (event.value === null || event.value === 0) { // PrimeVue InputNumber event.value
                                    form.trader_commission_rate = ''; // Оставляем пустым для null на бэке
                                }
                            }"
                            mode="decimal"
                            :minFractionDigits="0"
                            :maxFractionDigits="2"
                            placeholder="Пусто = комиссия шлюза"
                            suffix=" %"
                        />
                        <InputError class="mt-2" :message="form.errors.trader_commission_rate" />
                    </div>
 
              
                 

                    <div class="md:flex justify-between col-span-2 md:col-span-1 gap-4  lg:grid lg:grid-cols-2  ">
                    <div v-if="hasPayoutsAccess(form.role_id)">
                        <label class="block mt-3 text-sm font-medium text-foreground">Доступ к выплатам</label>
                        <Button
                            :label="form.payouts_enabled ? 'Включен' : 'Выключен'"
                            :icon="form.payouts_enabled ? 'pi pi-check-circle' : 'pi pi-times-circle'"
                            :severity="form.payouts_enabled ? 'success' : 'danger'"
                            @click="form.payouts_enabled = !form.payouts_enabled"
                            class="p-button-sm w-full mt-1"
                            outlined
                        />
                    </div>

                        <div class=" ">
                        <label class="block mt-3 text-sm font-medium text-foreground">Заблокирован</label>
                        <Button
                            :label="form.banned ? 'Да (Разблокировать)' : 'Нет (Заблокировать)'"
                            :icon="form.banned ? 'pi pi-lock-open' : 'pi pi-lock'"
                            :severity="form.banned ? 'success' : 'danger'"
                            @click="form.banned = !form.banned"
                            class="p-button-sm w-full mt-1"
                            outlined
                        />
                    </div>

</div>
                    <div class="md:flex justify-between col-span-2 md:col-span-1 gap-4 lg:grid lg:grid-cols-2  mb-5"> 
                  
       <div v-if="isTrader(form.role_id) || isAdmin(form.role_id)">
                        <div class="flex items-center mt-3">
                            <label class="block text-sm font-medium text-foreground">
                                Трафик
                            </label>
                            <i class="pi pi-info-circle ml-2 cursor-pointer text-muted-foreground" 
                               @click="trafficStatusOP.toggle($event)"></i>
                            
                            <OverlayPanel ref="trafficStatusOP">
                                <div class="p-3 text-sm">
                                    <p v-if="user.traffic_enabled_at && !form.stop_traffic">
                                        Был включен: <DateTime :data="user.traffic_enabled_at" />
                                    </p>
                                </div>
                            </OverlayPanel>
                        </div>
                        <Button
                            :label="form.stop_traffic ? 'Остановлен (Включить)' : 'Включен (Остановить)'"
                            :icon="form.stop_traffic ? 'pi pi-play' : 'pi pi-pause'"
                            :severity="form.stop_traffic ? 'success' : 'danger'"                            
                            @click="form.stop_traffic = !form.stop_traffic"
                            class="p-button-sm w-full mt-1"
                            outlined
                        />
                    </div>


                    <div v-if="isTrader(form.role_id) || isAdmin(form.role_id)">
                        <div class="flex items-center mt-3">
                            <label class="block text-sm font-medium text-foreground">
                                VIP статус
                            </label>
                            <i class="pi pi-info-circle ml-2 cursor-pointer text-muted-foreground" 
                               @click="vipStatusOP.toggle($event)"></i>
                            
                            <OverlayPanel ref="vipStatusOP">
                                <div class="p-3 text-sm">
                                    <p>VIP пользователи могут редактировать мин/макс сумму сделки.</p>
                                </div>
                            </OverlayPanel>
                        </div>
                        <Button
                            :label="form.is_vip ? 'Активен' : 'Не активен'"
                            :icon="form.is_vip ? 'pi pi-star-fill' : 'pi pi-star'"
                            :severity="form.is_vip ? 'success' : 'secondary'"
                            @click="form.is_vip = !form.is_vip"
                            class="p-button-sm w-full mt-1"
                            outlined
                        />
                    </div>

                </div>




                    

 

                    <div v-if="isTeamLeader(form.role_id) || isAdmin(form.role_id)">
                        <div class="flex items-center mb-2">
                            <label for="referral_commission_percentage" class="block text-sm font-medium text-foreground">
                                Комиссия от рефералов (%)
                            </label>
                            <i class="pi pi-info-circle ml-2 cursor-pointer text-muted-foreground" 
                               v-tooltip.right="'Процент для Team Leader со сделок привлеченных трейдеров'"></i>
                        </div>
                        <InputNumber
                            id="referral_commission_percentage"
                            class="w-full mt-1"
                            v-model="form.referral_commission_percentage"
                            :invalid="!!form.errors.referral_commission_percentage"
                            @input="form.clearErrors('referral_commission_percentage')"
                            mode="decimal"
                            :minFractionDigits="0"
                            :maxFractionDigits="2"
                            suffix=" %"
                        />
                        <InputError class="mt-2" :message="form.errors.referral_commission_percentage" />
                    </div>

 
                    <!-- <div class="md:col-span-2" v-if="!user.promo_code_id && (isTrader(form.role_id) || isAdmin(form.role_id))">
                        <label for="promo_code" class="block mb-2 text-sm font-medium text-gray-900 dark:text-gray-300">Промокод</label>
                        <InputText
                            id="promo_code"
                            type="text"
                            class="mt-1 block w-full"
                            v-model="form.promo_code"
                            autocomplete="off"
                            :invalid="!!form.errors.promo_code"
                            @input="form.clearErrors('promo_code')"
                        />
                        <div class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            Введите промокод, если пользователь был привлечен через него. Нельзя изменить после сохранения.
                        </div>
                        <InputError class="mt-2" :message="form.errors.promo_code" />
                    </div> -->

                    <!-- <div class="md:col-span-2" v-else-if="user.promo_code_id && (isTrader(form.role_id) || isAdmin(form.role_id))">
                        <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-gray-300">
                            Промокод
                            <i class="pi pi-info-circle ml-2 cursor-pointer text-gray-500" 
                               v-tooltip.right="'Пользователь был привлечен через этот промокод. Нельзя изменить.'"></i>
                        </label>
                        <div class="mt-1 p-3 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-md">
                            <span class="font-medium text-gray-700 dark:text-gray-300">{{ user.promo_code?.code }}</span>
                            <span v-if="user.promo_code?.team_leader?.email" class="text-sm text-gray-500 dark:text-gray-400 ml-2">
                                ({{ user.promo_code.team_leader.email }} - {{ user.promo_code.team_leader.commission_percentage || 0 }}%)
                            </span>
                        </div>
                    </div> -->

                    <div v-if="isTrader(form.role_id)" class="md:col-span-2">
                        <TraderTeamLeaders
                            v-model="form.trader_team_leaders"
                            :availableTeamLeaders="teamLeadersList"
                            :error="form.errors.trader_team_leaders"
                            :disabled="form.processing"
                        />
                    </div>

                    <div v-if="isMerchant(form.role_id)" class="md:col-span-2">
                        <TraderTeamLeaders
                            v-model="form.merchant_team_leaders"
                            :availableTeamLeaders="teamLeadersList"
                            :error="form.errors.merchant_team_leaders"
                            :disabled="form.processing"
                            title="Тимлидеры мерчанта"
                        />
                    </div>
                </div>

                <div class="flex items-center gap-4 mt-8 md:col-span-2">
                    <Button label="Сохранить" :loading="form.processing" type="submit" icon="pi pi-save" />
                    <Transition
                        enter-active-class="transition ease-in-out"
                        enter-from-class="opacity-0"
                        leave-active-class="transition ease-in-out"
                        leave-to-class="opacity-0"
                    >
                        <p v-if="form.recentlySuccessful" class="text-sm text-muted-foreground">Сохранено.</p>
                    </Transition>
                </div>
            </form>

            <div class="mt-10 pt-6 border-t border-border">
                <h3 class="text-lg font-medium text-foreground mb-4">Дополнительные действия</h3>

                <div class="space-y-4">
                    <div
                        v-show="user.has_2fa === true"
                        class="flex items-center justify-between p-4 bg-muted border border-border rounded-lg"
                    >
                        <div>
                            <h4 class="text-base font-medium text-foreground">Двухфакторная аутентификация</h4>
                            <p class="text-sm text-muted-foreground">Сброс 2FA позволит пользователю настроить его заново.</p>
                        </div>
                        <Button
                            label="Сбросить 2FA"
                            icon="pi pi-shield"
                            severity="danger"
                            @click="reset2fa"
                            type="button"
                        />
                    </div>
                </div>
            </div>
        </SecondaryPageSection>

        <ConfirmModal />
    </div>
</template>

<style>
.p-tooltip .p-tooltip-text {
    background: var(--popover);
    color: var(--popover-foreground);
    padding: 0.5rem;
    border-radius: 0.25rem;
    font-size: 0.875rem;
    max-width: 300px;
}

.p-overlaypanel {
    background: var(--popover);
    color: var(--popover-foreground);
    border: 1px solid var(--border);
    border-radius: 0.25rem;
}

.p-overlaypanel:before,
.p-overlaypanel:after {
    border-bottom-color: var(--popover) !important;
}
</style>
