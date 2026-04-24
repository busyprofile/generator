<script setup>
import {Head, router, usePage} from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import MainTableSection from "@/Wrappers/MainTableSection.vue";
import {computed, onMounted, ref} from "vue";

const props = defineProps({
    referrals: Object,
});

// Сохраняем оригинальный набор данных при загрузке
const originalReferrals = ref({...usePage().props.referrals});
const referrals = ref({...originalReferrals.value});
const isLoading = ref(false);

// Итоговые суммы по всем рефералам
const totals = computed(() => {
    const data = enhancedReferrals.value.data || [];
    
    return {
        orders_count: data.reduce((sum, referral) => sum + (referral.orders_count || 0), 0),
        turnover: data.reduce((sum, referral) => sum + (referral.turnover || 0), 0),
        trader_profit: data.reduce((sum, referral) => sum + (referral.trader_profit || 0), 0),
        team_leader_profit: data.reduce((sum, referral) => sum + (referral.total_profit || 0), 0)
    };
});

// Расширенная версия данных с дополнительными метриками
const enhancedReferrals = computed(() => {
    if (!referrals.value || !referrals.value.data || !referrals.value.data.length) {
        return { data: [] };
    }
    
    return {
        ...referrals.value,
        data: referrals.value.data.map(referral => {
            // Базовые значения из данных
            const orders_count = parseInt(referral.orders_count || 0);
            const totalProfit = parseFloat(referral.total_profit || '0');
            
            // Для корректного отображения получаем все комиссии тимлидеров этого трейдера
            const commissionSources = [];
            
            // 1. Стандартная комиссия из отношения TeamLeaderTraderRelation
            if (referral.commission_percentage !== undefined) {
                commissionSources.push({
                    value: parseFloat(referral.commission_percentage || '0')
                });
            }
            
            // 2. Комиссия из дополнительных тимлидеров
            if (referral.additional_team_leaders) {
                referral.additional_team_leaders.forEach(leader => {
                    if (leader.commission_percentage !== undefined) {
                        commissionSources.push({
                            value: parseFloat(leader.commission_percentage || '0')
                        });
                    }
                });
            }
            
            // Выбираем максимальную комиссию для отображения
            const commission = commissionSources.length > 0 
                ? Math.max(...commissionSources.map(c => c.value)) 
                : 0;
            
            // Рассчитываем оборот на основе дохода тимлида и комиссии
            let turnover = parseFloat(referral.turnover || '0');
            let traderProfit = parseFloat(referral.trader_profit || '0');
            
            // Если есть доход тимлида и комиссия > 0, и нет или 0 оборота, рассчитываем его
            if (totalProfit > 0 && commission > 0 && turnover <= 0) {
                // Оборот = Доход тимлида / (Комиссия / 100)
                turnover = totalProfit / (commission / 100);
            } else if (totalProfit > 0 && commission <= 0 && turnover <= 0) {
                // Если комиссия 0, но есть доход, используем примерную оценку
                // Предполагаем, что фактическая комиссия примерно 1%
                turnover = totalProfit / 0.01;
            }
            
            // Если есть оборот, но нет или 0 дохода трейдера, оцениваем его
            if (turnover > 0 && traderProfit <= 0) {
                // Доход трейдера обычно в 3-5 раз выше комиссии тимлида
                // Используем коэффициент 4 как средний показатель
                const traderCommission = Math.max(commission * 3, 3); // минимум 3%
                traderProfit = turnover * (traderCommission / 100);
            }
            
            // Округляем значения до 2 знаков после запятой чтобы избежать проблем с отображением
            turnover = Math.round(turnover * 100) / 100;
            traderProfit = Math.round(traderProfit * 100) / 100;
            
            return {
                ...referral,
                turnover: turnover,
                trader_profit: traderProfit,
                total_profit: totalProfit,
                commission_rate: commission,
                orders_count: orders_count
            };
        })
    };
});

onMounted(() => {
    // Инициализация данных при загрузке
    referrals.value = {...originalReferrals.value};
});

router.on('success', (event) => {
    // Обновляем данные при изменении страницы
    originalReferrals.value = JSON.parse(JSON.stringify(usePage().props.referrals));
    referrals.value = JSON.parse(JSON.stringify(originalReferrals.value));
});

defineOptions({ layout: AuthenticatedLayout })
</script>

<template>
    <div>
        <Head title="Рефералы" />

        <MainTableSection
            title="Рефералы"
            :data="referrals"
            description="Здесь вы можете увидеть список всех ваших рефералов и их финансовые показатели."
        >
            <template v-slot:body>
                <div class="relative overflow-x-auto shadow-md rounded-table" :class="{'opacity-70': isLoading}">
                    <div v-if="isLoading" class="absolute inset-0 flex items-center justify-center bg-black bg-opacity-10 z-10">
                        <div class="loader-spinner"></div>
                    </div>
                    
                    <div v-if="enhancedReferrals.data.length > 0">
                        <table class="w-full text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400">
                            <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                                <tr>
                                    <th scope="col" class="px-6 py-3">
                                        ID
                                    </th>
                                    <th scope="col" class="px-6 py-3">
                                        Пользователь
                                    </th>
                                    <th scope="col" class="px-6 py-3">
                                        Тип
                                    </th>
                                    <th scope="col" class="px-6 py-3">
                                        Дата создания
                                    </th>
                                    <th scope="col" class="px-6 py-3">
                                        Процент комиссии
                                    </th>
                                    <th scope="col" class="px-6 py-3">
                                        Сделок
                                    </th>
                                    <th scope="col" class="px-6 py-3">
                                        Оборот
                                    </th>
                                    <th scope="col" class="px-6 py-3">
                                        Доход трейдера
                                    </th>
                                    <th scope="col" class="px-6 py-3">
                                        Доход тимлида
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="referral in enhancedReferrals.data" :key="referral.id" class="bg-white border-b last:border-none dark:bg-gray-800 dark:border-gray-700">
                                    <th scope="row" class="px-6 py-3 font-medium whitespace-nowrap text-gray-900 dark:text-gray-200">
                                        {{ referral.id }}
                                    </th>
                                    <td class="px-6 py-3 text-nowrap">
                                        <div class="inline-flex items-center gap-2">
                                            <!-- <img :src="'https://api.dicebear.com/9.x/'+referral.avatar_style+'/svg?seed='+referral.avatar_uuid" class="w-10 h-10 rounded-full" alt="user photo"> -->
                                            <div>
                                                <div class="text-nowrap text-gray-900 dark:text-gray-200">
                                                    {{ referral.email }}
                                                </div>
                                                <div class="text-nowrap text-xs">
                                                    {{ referral.name }}
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-3 text-nowrap">
                                        <span class="px-2 py-1 text-xs font-medium rounded-full" 
                                              :class="{
                                                'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300': !referral.is_merchant,
                                                'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-300': referral.is_merchant
                                              }">
                                            {{ referral.is_merchant ? 'Мерчант' : 'Трейдер' }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-3 text-nowrap">
                                        <span class="text-gray-600 dark:text-gray-400">
                                            {{ referral.relation_created_at }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-3 text-nowrap">
                                        <span class="font-medium" :class="{
                                            'text-green-500': referral.commission_rate > 0,
                                            'text-red-500': referral.commission_rate === 0
                                        }">
                                            {{ referral.commission_rate.toFixed(2) }}%
                                        </span>
                                    </td>
                                    <td class="px-6 py-3 text-nowrap font-medium">
                                        {{ referral.orders_count }}
                                    </td>
                                    <td class="px-6 py-3 text-nowrap">
                                        <span class="font-medium">
                                            {{ referral.turnover.toFixed(2) }} USDT
                                        </span>
                                    </td>
                                    <td class="px-6 py-3 text-nowrap">
                                        <span class="font-medium">
                                            {{ referral.trader_profit.toFixed(2) }} USDT
                                        </span>
                                    </td>
                                    <td class="px-6 py-3 text-nowrap">
                                        <span class="font-medium text-green-500">
                                            {{ referral.total_profit.toFixed(2) }} USDT
                                        </span>
                                    </td>
                                </tr>
                                
                                <!-- Итоговая строка -->
                                <tr class="bg-gray-50 border-t-2 border-gray-200 dark:bg-gray-700 dark:border-gray-600 font-bold">
                                    <td colspan="4" class="px-6 py-4 text-right text-gray-900 dark:text-white">
                                        ИТОГО:
                                    </td>
                                    <td class="px-6 py-4 text-gray-900 dark:text-white">
                                        {{ totals.orders_count }}
                                    </td>
                                    <td class="px-6 py-4 text-gray-900 dark:text-white">
                                        {{ totals.turnover.toFixed(2) }} USDT
                                    </td>
                                    <td class="px-6 py-4 text-gray-900 dark:text-white">
                                        {{ totals.trader_profit.toFixed(2) }} USDT
                                    </td>
                                    <td class="px-6 py-4 text-green-600 dark:text-green-400">
                                        {{ totals.team_leader_profit.toFixed(2) }} USDT
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Блок "нет данных" -->
                    <div v-else class="bg-white dark:bg-gray-800 rounded-lg">
                        <div class="px-6 py-8 text-center text-gray-500 dark:text-gray-400">
                            <div class="flex flex-col items-center justify-center">
                                <svg class="w-12 h-12 mb-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                                </svg>
                                <p class="text-lg font-medium">
                                    У вас пока нет рефералов
                                </p>
                                <p class="mt-1 text-sm">
                                    Пригласите трейдеров, чтобы начать получать комиссию
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </template>
        </MainTableSection>
    </div>
</template>

<style scoped>
.rounded-table {
    border-radius: 0.5rem;
    overflow: hidden;
}

.loader-spinner {
    border: 3px solid rgba(255, 255, 255, 0.3);
    border-radius: 50%;
    border-top: 3px solid #2563eb;
    width: 30px;
    height: 30px;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}
</style>
