<script setup>
import { ref, inject, defineEmits } from 'vue';
import { useModalStore } from "@/store/modal.js";
import { usePage } from "@inertiajs/vue3";
import { useViewStore } from "@/store/view.js";
import DepositModalAuto from '@/Modals/Wallet/DepositModalAuto.vue';
import Card from 'primevue/card';
import Tag from 'primevue/tag';
import Divider from 'primevue/divider';
import Button from 'primevue/button';

const viewStore = useViewStore();
const modalStore = useModalStore();

const walletStats = usePage().props.walletStats;
const user = usePage().props.user;

// Допустим, по умолчанию валюта
const primaryCurrency = walletStats.currency.primary.toUpperCase();

// Инжектим функцию из AuthenticatedLayout
const openGlobalWithdrawalModal = inject('openGlobalWithdrawalModal');

// Добавляем emit для setBalanceType, если его еще нет или он был закомментирован
const emit = defineEmits(['setBalanceType']); 

// Локальное состояние для модального окна пополнения (для неадмина)
const showDepositModalAuto = ref(false);

// Эта функция останется для кнопки "Пополнить" НЕ для админа
const openTraderDepositModal = () => {
  showDepositModalAuto.value = true;
};

// Новая функция для кнопки "Пополнить" для АДМИНА
const openAdminTrustDepositModal = () => {
  emit('setBalanceType', 'trust');
  modalStore.openModal('deposit', { user: usePage().props.user }); // Убедимся, что user передается
};
</script>

<template>
  <Card class="p-card p-component">
    <template #title>
      <div class="flex items-center justify-between">
        <span>Доверенный баланс</span>
        <Tag severity="info" value="TRUST" />
      </div>
    </template>
    <template #content>
      <div class="flex items-center justify-between mb-3 flex-wrap gap-2">
        <p class="text-lg md:text-2xl font-semibold text-gray-900 dark:text-white mb-0">
          {{ walletStats.base.trustAmount }} {{ primaryCurrency }}
        </p>
        <div class="flex flex-col sm:flex-row gap-1">
          <div class="flex items-center">
            <span class="text-gray-500 dark:text-gray-400 mr-1">Вывод:</span>
            <span class="font-medium text-gray-900 dark:text-gray-200">{{ walletStats.lockedForWithdrawalBalances.trust.primary }} {{ primaryCurrency }}</span>
          </div>
          <div class="flex items-center">
            <span class="text-gray-500 dark:text-gray-400 mr-1">Резерв:</span>
            <span class="font-medium text-gray-900 dark:text-gray-200">{{ walletStats.base.trustReserveAmount }} {{ primaryCurrency }}</span>
          </div>
        </div>
      </div>
      <Divider class="my-3" />
    </template>
    <template #footer>
      <div class="flex flex-col sm:flex-row justify-between gap-2 items-left align-items-left">
        <div class="flex items-center">
          <i class="pi pi-verified mr-2 text-gray-500 dark:text-gray-400"></i>
          <span class="text-gray-500 dark:text-gray-400 mr-1">Максимальный резерв</span>
          <span class="font-medium text-gray-900 dark:text-gray-200">{{ walletStats.maxReserveBalance }} {{ primaryCurrency }}</span>
        </div>
        <div class="flex gap-2 justify-end">
          <Button
            v-if="viewStore.isAdminViewMode"
            icon="pi pi-arrow-down-right"
            label="Вывести"
            severity="secondary"
            size="small"
            @click.prevent="openGlobalWithdrawalModal('trust', {user: user})"
          />
          <Button
            v-if="viewStore.isAdminViewMode"
            icon="pi pi-plus"
            label="Пополнить"
            size="small"
            @click="openAdminTrustDepositModal"
          />
          <Button
            v-else
            icon="pi pi-arrow-down-right"
            label="Вывести"
            severity="secondary"
            size="small"
            @click.prevent="openGlobalWithdrawalModal('trust', {user: user})"
          />
          <Button
            v-else
            icon="pi pi-plus"
            label="Пополнить"
            size="small"
            @click="openTraderDepositModal"
          />
        </div>
      </div>
      <DepositModalAuto
        v-if="showDepositModalAuto"
        :balanceType="'trust'"
        @closeModal="showDepositModalAuto = false"
      />
    </template>
  </Card>
</template>

<style scoped>
.p-card {
  border-radius: 1rem;
  box-shadow: 0 2px 8px 0 rgba(0,0,0,0.04);
  background: var(--surface-card, #fff);
}
.p-card-title {
  font-size: 1.1rem;
  font-weight: 600;
}
.p-card-content {
  padding-bottom: 0;
}
.p-card-footer {
  padding-top: 0;
}
</style>
