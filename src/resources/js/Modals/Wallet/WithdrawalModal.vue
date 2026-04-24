<script setup>
import { storeToRefs } from 'pinia';
import { useModalStore } from '@/store/modal.js';
import { useForm, usePage } from '@inertiajs/vue3';
import { useViewStore } from '@/store/view.js';
import Dialog from 'primevue/dialog';
import Card from 'primevue/card';
import InputNumber from 'primevue/inputnumber';
import InputText from 'primevue/inputtext';
import Button from 'primevue/button';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputHelper from '@/Components/InputHelper.vue';
import Tag from 'primevue/tag';

const props = defineProps({
  balanceType: { type: String },
});

const page = usePage();
const total_trust_withdrawable_amount = page.props.total_trust_withdrawable_amount;
const total_merchant_withdrawable_amount = page.props.total_merchant_withdrawable_amount;

const modalStore = useModalStore();
const { withdrawalModal } = storeToRefs(modalStore);
const viewStore = useViewStore();

const close = () => {
  modalStore.closeModal('withdrawal');
};

const form = useForm({
  amount: null,
  address: null,
  balance_type: props.balanceType,
});

const withdraw = () => {
  // Обновляем balance_type перед отправкой, на случай если props.balanceType изменился
  form.balance_type = props.balanceType;
  if (viewStore.isAdminViewMode) {
    form.post(route('admin.users.wallet.withdraw', withdrawalModal.value.params.user.id), {
      preserveScroll: true,
      onSuccess: () => {
        modalStore.closeAll();
        form.reset();
      },
    });
  }
  if (viewStore.isTraderViewMode || viewStore.isMerchantViewMode || viewStore.isTeamLeaderViewMode) {
    form.post(route('invoice.store'), {
      preserveScroll: true,
      onSuccess: () => {
        modalStore.closeAll();
        form.reset();
      },
    });
  }
};
</script>

<template>
  <Dialog v-model:visible="withdrawalModal.showed" modal :closable="false" :dismissableMask="true" class="p-0" :style="{ maxWidth: '470px', width: '95vw' }" @hide="close">
          <template #header>
        <div class="flex items-center justify-between w-full  ">
          <div class="flex items-center gap-2">
            <Tag v-if="balanceType === 'trust'" severity="info" value="TRUST"></Tag>
            <Tag v-if="balanceType === 'merchant'" severity="info" value="MERCHANT"></Tag>
            <Tag v-if="balanceType === 'teamleader'" severity="info" value="TEAMLEADER"></Tag>
            <span class="text-lg font-bold text-gray-900 dark:text-white">
              {{ balanceType === 'trust'
                ? 'Вывод с траст баланса'
                : balanceType === 'merchant'
                  ? 'Вывод с мерчант баланса'
                  : 'Вывод с баланса тимлидера'
              }}
            </span>
        
          </div>
          <Button icon="pi pi-times" text rounded severity="danger" @click="close" />
        </div>
      </template>
    <Card class="max-w-lg mx-auto p-0 rounded-2xl shadow-2xl bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 animate-fadein">
 
      <template #content>
        <div class="p-6">
          <div class="mb-4 text-center text-gray-900 dark:text-gray-200">
            Введите сумму, которую хотите вывести с баланса в USDT, и нажмите <b>«Продолжить»</b>
          </div>
          <div class="flex flex-col gap-3 items-center">
            <div class="w-full max-w-xs">
              <InputLabel for="amount" value="Сумма вывода" :error="!!form.errors.amount" />
              <!-- Минимальная сумма для вывода -->
              <InputNumber
                id="amount"
                v-model="form.amount"
                mode="decimal"
                :minFractionDigits="2"
                :maxFractionDigits="2"
                :min="0.01"
                placeholder="Сумма в USDT"
                class="w-full mt-1"
                inputClass="text-lg py-2 px-4 rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900 text-gray-900 dark:text-gray-300 w-full"
                autofocus
                showButtons
                buttonLayout="horizontal"
                incrementButtonIcon="pi pi-plus"
                decrementButtonIcon="pi pi-minus"
                @input="form.clearErrors('amount')"
              />
              <InputError class="mt-2" :message="form.errors.amount" />
              <template v-if="!form.errors.amount">
                  <InputHelper v-if="balanceType === 'trust'" class="mt-1" :model-value="'Максимум: ' + total_trust_withdrawable_amount + ' USDT'" />
                  <InputHelper v-if="balanceType === 'merchant'" class="mt-1" :model-value="'Максимум: ' + total_merchant_withdrawable_amount + ' USDT'" />
              </template>
            </div>

            <div class="w-full max-w-xs mt-4" v-if="viewStore.isTraderViewMode || viewStore.isMerchantViewMode || viewStore.isTeamLeaderViewMode">
              <InputLabel for="address" value="Адрес для вывода (USDT TRC-20)" :error="!!form.errors.address" />
              <InputText
                id="address"
                v-model="form.address"
                placeholder="Ваш USDT TRC-20 Адрес"
                class="w-full mt-1 text-lg py-2 px-4 rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900 text-gray-900 dark:text-gray-300"
                :invalid="!!form.errors.address"
                @input="form.clearErrors('address')"
              />
              <InputError class="mt-2" :message="form.errors.address" />
            </div>

            <Button
              label="Вывести"
              icon="pi pi-arrow-right-to-bracket transform rotate-90"
              class="w-full max-w-xs mt-6"
              size="large"
              @click="withdraw"
              :loading="form.processing"
              severity="success"
            />
          </div>
        </div>
      </template>
    </Card>
  </Dialog>
</template>

<style scoped>
.animate-fadein {
  animation: fadein 0.5s;
}
@keyframes fadein {
  from { opacity: 0; transform: translateY(20px); }
  to { opacity: 1; transform: none; }
}
</style>
