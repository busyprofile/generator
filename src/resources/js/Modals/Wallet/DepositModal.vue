<script setup>
import { storeToRefs } from 'pinia';
import { useModalStore } from '@/store/modal.js';
import { useForm } from '@inertiajs/vue3';
import Dialog from 'primevue/dialog';
import Card from 'primevue/card';
import InputNumber from 'primevue/inputnumber';
import InputText from 'primevue/inputtext'; // Используем InputText для хэша
import Button from 'primevue/button';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputHelper from '@/Components/InputHelper.vue';
import Tag from 'primevue/tag';

const props = defineProps({
  balanceType: { type: String },
});

const modalStore = useModalStore();
const { depositModal } = storeToRefs(modalStore);

const close = () => {
  modalStore.closeModal('deposit');
};

const form = useForm({
  amount: null,
  balance_type: null,
  tx_hash: null,
});

const deposit = () => {
  form
    .transform((data) => {
      data.balance_type = props.balanceType;
      return data;
    })
    .post(route('admin.users.wallet.deposit', depositModal.value.params.user.id), {
      preserveScroll: true,
      onSuccess: () => {
        modalStore.closeAll();
        form.reset();
      },
    });
};
</script>

<template>
  <Dialog v-model:visible="depositModal.showed" modal :closable="false" :dismissableMask="true" class="p-0" :style="{ maxWidth: '470px', width: '95vw' }" @hide="close">
          <template #header>
        <div class="flex items-center justify-between w-full   ">
            
          <div class="flex items-center gap-2">
            <Tag v-if="balanceType === 'trust'" severity="info" value="TRUST"></Tag>
            <Tag v-if="balanceType === 'merchant'" severity="info" value="MERCHANT"></Tag>
            <Tag v-if="balanceType === 'teamleader'" severity="info" value="TEAMLEADER"></Tag>
            <span class="text-lg font-bold text-gray-900 dark:text-white">
              {{ balanceType === 'trust'
                ? 'Пополнение траст баланса'
                : balanceType === 'merchant'
                  ? 'Пополнение мерчант баланса'
                  : 'Пополнение баланса тимлидера'
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
            Введите сумму пополнения в USDT и нажмите <b>«Продолжить»</b>
          </div>
          <div class="flex flex-col gap-3 items-center">
            <div class="w-full max-w-xs">
              <InputLabel for="amount" value="Сумма пополнения" :error="!!form.errors.amount" />
              <InputNumber
                id="amount"
                v-model="form.amount"
                mode="decimal"
                :minFractionDigits="2"
                :maxFractionDigits="2"
                :min="1"
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
              <template v-if="balanceType === 'trust'">
                  <InputHelper v-if="!form.errors.amount" class="mt-1" model-value="Если резерв меньше 1000 USDT, то часть депозита зачислится в резерв."></InputHelper>
              </template>
            </div>

            <div class="w-full max-w-xs mt-4">
              <InputLabel for="tx_hash" value="Хэш транзакции" :error="!!form.errors.tx_hash" />
              <InputText
                id="tx_hash"
                v-model="form.tx_hash"
                placeholder="Хэш транзакции (опционально)"
                class="w-full mt-1 text-lg py-2 px-4 rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900 text-gray-900 dark:text-gray-300"
                :invalid="!!form.errors.tx_hash"
                @input="form.clearErrors('tx_hash')"
              />
              <InputError class="mt-2" :message="form.errors.tx_hash" />
              <InputHelper v-if="!form.errors.tx_hash" class="mt-1" model-value="Необязательное поле. Укажите хэш транзакции, если есть."></InputHelper>
            </div>

            <Button
              label="Пополнить"
              icon="pi pi-check"
              class="w-full max-w-xs mt-6"
              size="large"
              @click="deposit"
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
