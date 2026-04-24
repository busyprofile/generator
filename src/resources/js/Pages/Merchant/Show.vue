<script setup>
import {Head, router, usePage} from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import GoBackButton from "@/Components/GoBackButton.vue";
import {onMounted, ref, computed, onUnmounted} from "vue";
import Statistics from "@/Pages/Merchant/Tabs/Statistics.vue";
import Payments from "@/Pages/Merchant/Tabs/Payments.vue";
import Settings from "@/Pages/Merchant/Tabs/Settings.vue";
import {useViewStore} from "@/store/view.js";
import AlertError from "@/Components/Alerts/AlertError.vue";
import AlertInfo from "@/Components/Alerts/AlertInfo.vue";
import SelectButton from 'primevue/selectbutton';
import Message from 'primevue/message';

const tab = ref('statistics');
const viewStore = useViewStore();
const merchant = usePage().props.merchant;

const screenWidth = ref(window.innerWidth);
const updateScreenWidth = () => { screenWidth.value = window.innerWidth; };
onMounted(() => { window.addEventListener('resize', updateScreenWidth); });
onUnmounted(() => { window.removeEventListener('resize', updateScreenWidth); });
const selectButtonSize = computed(() => { return screenWidth.value < 768 ? 'small' : null; });

const tabOptions = ref([
    { label: 'Статистика', value: 'statistics', icon: 'pi pi-chart-line' },
    { label: 'Платежи', value: 'payments', icon: 'pi pi-dollar' },
    { label: 'Настройки', value: 'settings', icon: 'pi pi-cog' }
]);

const openPage = (page = null) => {
    let data = {
        tab: tab.value
    };
    if (page) {
        data.page = page;
    }
    router.visit(route(route().current(), merchant.id), {
        data: data,
        preserveScroll: true,
        preserveState: false,
        only: []
    })
}

onMounted(() => {
    window.addEventListener('resize', updateScreenWidth);

    let urlParams = new URLSearchParams(window.location.search);
    let tabParam = urlParams.get('tab');
    if (tabParam && tabOptions.value.some(opt => opt.value === tabParam)) {
        tab.value = tabParam;
    } else {
        tab.value = 'statistics';
    }
});

onUnmounted(() => {
    window.removeEventListener('resize', updateScreenWidth);
});

defineOptions({ layout: AuthenticatedLayout })
</script>

<template>
    <div>
        <Head :title="'Мерчант - ' + merchant.name"/>

        <div class="mx-auto space-y-4">
            <div class="mb-3">
                <GoBackButton @click="router.visit(route(viewStore.adminPrefix + 'merchants.index'))"/>
            </div>
            <div>
                <section>
                    <div>
                        <div class="mt-6 space-y-6">
                            <SelectButton
                                v-model="tab"
                                :options="tabOptions"
                                optionLabel="label"
                                optionValue="value"
                                @change="openPage()"
                                aria-labelledby="merchant-tabs"
                                :size="selectButtonSize"
                            >
                                <template #option="slotProps">
                                    <div class="flex items-center">
                                        <i :class="['pi', slotProps.option.icon, 'mr-2']"></i>
                                        <span>{{ slotProps.option.label }}</span>
                                    </div>
                                </template>
                            </SelectButton>

                            <Message v-if="! merchant.validated_at" severity="warn" :closable="false">
                                <span class="font-medium">Внимание!</span> Мерчант находится на модерации.
                            </Message>
                            <Message v-if="merchant.banned_at" severity="error" :closable="false">
                                <span class="font-medium">Внимание!</span> Мерчант заблокирован администратором.
                            </Message>

                            <AlertError :message="$page.props.flash.error"></AlertError>
                            <AlertInfo :message="$page.props.flash.message"></AlertInfo>

                            <Statistics v-show="tab === 'statistics'"/>
                            <Payments v-show="tab === 'payments'" @openPage="openPage"/>
                            <Settings v-show="tab === 'settings'"/>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </div>
</template>
