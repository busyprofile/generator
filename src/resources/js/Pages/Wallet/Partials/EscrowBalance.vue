<script setup>
import {useModalStore} from "@/store/modal.js";
import {router, usePage} from "@inertiajs/vue3";
import {useViewStore} from "@/store/view.js";
import {ref} from "vue";
import Card from 'primevue/card';
import Tag from 'primevue/tag';
import Divider from 'primevue/divider';

const viewStore = useViewStore();
const modalStore = useModalStore();

const user = usePage().props.user;
const walletStats = usePage().props.walletStats;
const escrowBalance = ref({
    primary: walletStats.escrowBalances.orders.balance.primary,
    secondary: walletStats.escrowBalances.orders.balance.secondary,
    count: walletStats.escrowBalances.orders.count,
});
const currency = ref({
    primary: walletStats.currency.primary.toUpperCase(),
    secondary: walletStats.currency.secondary.toUpperCase(),
});

router.on('success', (event) => {
    escrowBalance.value = {
        primary: walletStats.escrowBalances.orders.balance.primary,
        secondary: walletStats.escrowBalances.orders.balance.secondary,
        count: walletStats.escrowBalances.orders.count,
    };
    currency.value = {
        primary: walletStats.currency.primary.toUpperCase(),
        secondary: walletStats.currency.secondary.toUpperCase(),
    };
})
</script>

<template>
  <Card class="p-card p-component">
    <template #title>
      <div class="flex items-center justify-between">
        <div class="flex items-center gap-2"> 
          <span>Холд <span class="md:inline-block hidden">(Escrow)</span></span>
        </div>
        <Tag severity="warn" :value="`ESCROW`" />
      </div>
      
    </template>
    <template #content>

 <div class="flex items-center justify-between mb-3 flex-wrap gap-2"> 
      <div class="flex    mb-3 flex-wrap gap-2 items-end ">

        <p class="text-lg md:text-2xl font-semibold text-gray-900 dark:text-white mb-0">
          {{ escrowBalance.primary }} {{ currency.primary }}
        </p>
 
        <div class="flex items-center gap-2"> 
          <span class="text-gray-500 dark:text-gray-400">{{ escrowBalance.secondary }} {{ currency.secondary }}</span>
        </div>

      </div>

        <div class="flex items-center gap-2"> 
        <span class="text-gray-500 dark:text-gray-400">Сделок:</span>
        <Tag :value="escrowBalance.count" severity="info" v-if="escrowBalance.count > 0" />
        <span v-else class="font-medium text-gray-900 dark:text-gray-200">0</span>
      </div>
 </div>
      <Divider class="my-2" />
    </template>
    <template #footer>
    <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
        Средства, находящиеся в холде до завершения сделки
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
