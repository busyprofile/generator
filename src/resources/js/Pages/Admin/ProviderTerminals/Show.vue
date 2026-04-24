<script setup>
import { Head, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import GoBackButton from '@/Components/GoBackButton.vue';
import { onMounted, ref, computed, onUnmounted } from 'vue';
import SelectButton from 'primevue/selectbutton';
import Message from 'primevue/message';
import StatisticsTab from './Tabs/Statistics.vue';
import PaymentsTab from './Tabs/Payments.vue';
import SettingsTab from './Tabs/Settings.vue';

const props = defineProps({
    terminal: Object,
    merchants: Array,
    detailTypes: Array,
    statistics: Object,
    orders: Object,
    integrationFields: {
        type: Array,
        default: () => [],
    },
});

const terminal = computed(() => props.terminal ?? {});
const merchantsList = computed(() => props.merchants ?? []);
const enabledTypes = ref([...(terminal.value.enabled_detail_types ?? [])]);

const tab = ref('statistics');
const tabOptions = ref([
    { label: 'Статистика', value: 'statistics', icon: 'pi pi-chart-line' },
    { label: 'Платежи', value: 'payments', icon: 'pi pi-dollar' },
    { label: 'Настройки', value: 'settings', icon: 'pi pi-cog' }
]);

const screenWidth = ref(window.innerWidth);
const updateScreenWidth = () => { screenWidth.value = window.innerWidth; };
const selectButtonSize = computed(() => screenWidth.value < 768 ? 'small' : null);

onMounted(() => window.addEventListener('resize', updateScreenWidth));
onUnmounted(() => window.removeEventListener('resize', updateScreenWidth));

const goBack = () => router.visit(route('admin.provider-terminals.index'));

defineOptions({ layout: AuthenticatedLayout });
</script>

<template>
    <div>
        <Head :title="`Провайдер терминал - ${terminal.name}`" />

        <div class="mx-auto space-y-4">
            <div class="mb-3">
                <GoBackButton @click="goBack" />
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
                                aria-labelledby="terminal-tabs"
                                :size="selectButtonSize"
                            >
                                <template #option="slotProps">
                                    <div class="flex items-center">
                                        <i :class="['pi', slotProps.option.icon, 'mr-2']"></i>
                                        <span>{{ slotProps.option.label }}</span>
                                    </div>
                                </template>
                            </SelectButton>

                            <Message v-if="!terminal?.is_active" severity="warn" :closable="false">
                                <span class="font-medium">Внимание!</span> Терминал выключен.
                            </Message>

                            <StatisticsTab v-show="tab === 'statistics'" :statistics="statistics" />
                            <PaymentsTab v-show="tab === 'payments'" :orders="orders" :terminal-id="terminal.id" />
                            <SettingsTab v-show="tab === 'settings'" :terminal="terminal" :merchants="merchantsList" :integration-fields="integrationFields" />
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </div>
</template>
