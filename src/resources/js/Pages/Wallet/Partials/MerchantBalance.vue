<script setup>
import {useModalStore} from "@/store/modal.js";
import {router, usePage} from "@inertiajs/vue3";
import {useViewStore} from "@/store/view.js";
import {ref, inject} from "vue";
import Card from 'primevue/card';
import Tag from 'primevue/tag';
import Divider from 'primevue/divider';
import Button from 'primevue/button';

const viewStore = useViewStore();
const modalStore = useModalStore();

const walletStats = ref(usePage().props.walletStats);
const user = usePage().props.user;
const primaryCurrency = walletStats.value?.currency?.primary?.toUpperCase() ?? '';

const openGlobalWithdrawalModal = inject('openGlobalWithdrawalModal');

const emit = defineEmits(['setBalanceType']);

router.on('success', (event) => {
    walletStats.value = usePage().props.walletStats;
})

const setBalanceTypeForDeposit = (type) => {
    emit('setBalanceType', type);
};
</script>

<template>
  <Card class="p-card p-component">
    <template #title>
      <div class="flex items-center justify-between">
        <div class="flex items-center gap-2"> 
          <span>Баланс мерчанта</span>
        </div>
        <Tag severity="info" value="MERCHANT" />
      </div>
    </template>
    <template #content>
      <div class="flex mb-3 flex-wrap gap-2 items-end">
        <p class="text-lg md:text-2xl font-semibold text-gray-900 dark:text-white mb-0">
          {{ walletStats.totalAvailableBalances.merchant.primary }} {{ primaryCurrency }}
        </p>
        <div class="flex items-center gap-2">
          <span class="text-gray-500 dark:text-gray-400">Вывод:</span>
          <span class="font-medium text-gray-900 dark:text-gray-200">{{ walletStats.lockedForWithdrawalBalances.merchant.primary }} {{ primaryCurrency }}</span>
        </div>
        <div class="flex gap-2 ml-auto">
          <Button
            v-if="viewStore.isAdminViewMode"
            icon="pi pi-arrow-down-right"
            label="Вывести"
            severity="secondary"
            size="small"
            @click.prevent="openGlobalWithdrawalModal('merchant', {user: user})"
          />
          <Button
            v-if="viewStore.isAdminViewMode"
            icon="pi pi-plus"
            label="Пополнить"
            size="small"
            @click.prevent="modalStore.openDepositModal({user}); setBalanceTypeForDeposit('merchant')"
          />
          <Button
            v-else
            icon="pi pi-arrow-down-right"
            label="Вывести"
            severity="secondary"
            size="small"
            @click.prevent="openGlobalWithdrawalModal('merchant', {user: user})"
          />
        </div>
      </div>
      <Divider class="my-2" />
    </template>
    <template #footer>
      <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
        Баланс для операций мерчанта (приём и вывод средств)
      </div>
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
