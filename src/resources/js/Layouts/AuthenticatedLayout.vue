<script setup>
import {usePage, router, Link, useForm} from '@inertiajs/vue3';
import {computed, onMounted, ref, watch, nextTick, provide} from 'vue'
import ViewModeSwitcher from "@/Layouts/Partials/ViewModeSwitcher.vue";
import TraderMenu from "@/Layouts/Partials/TraderMenu.vue";
import AdminMenu from "@/Layouts/Partials/AdminMenu.vue";
import MerchantMenu from "@/Layouts/Partials/MerchantMenu.vue";
import {useViewStore} from "@/store/view.js";
import {useUserStore} from "@/store/user.js";
import OnlineSwitcher from "@/Layouts/Partials/OnlineSwitcher.vue";
import TeamLeaderMenu from "@/Layouts/Partials/TeamLeaderMenu.vue";
import SupportMenu from "@/Layouts/Partials/SupportMenu.vue";
import Menu from 'primevue/menu';
import Avatar from 'primevue/avatar';
import Button from 'primevue/button';
import Sidebar from 'primevue/sidebar';
import OverlayPanel from 'primevue/overlaypanel';

import { useModalStore } from "@/store/modal.js";
import DepositModalAuto from '@/Modals/Wallet/DepositModalAuto.vue';
import WithdrawalModal from "@/Modals/Wallet/WithdrawalModal.vue";
import Card from 'primevue/card';
import Tag from 'primevue/tag';
import Divider from 'primevue/divider';

const viewStore = useViewStore();
const userStore = useUserStore();

// Получаем данные для счетчиков меню
const menuData = ref(usePage().props.menu);
const rates = ref(usePage().props.data.rates);
const role = usePage().props.auth.role;
const showAllRates = ref(false);
const isImpersonated = ref(usePage().props.auth.is_impersonated);
const isDarkMode = ref(false);
const isSidebarVisible = ref(false);
const user = computed(() => usePage().props.auth.user);
const walletProps = computed(() => usePage().props.data.wallet);
const walletStatsForBalance = computed(() => usePage().props.walletStats);

const modalStore = useModalStore();
const walletStats = usePage().props.walletStats;

const emit = defineEmits(['setBalanceType']);
const setBalanceType = (type) => {
  emit('setBalanceType', type);
};

// Локальное состояние для модального окна пополнения (для неадмина)
const showDepositModalAuto = ref(false);

// Функция для открытия модального окна пополнения для неадмина
const openDepositModal = () => {
  showDepositModalAuto.value = true;
  setBalanceType('trust');
};

// Для мобильного меню
const sidebarVisible = ref(false);

// Для профиля
const profileOp = ref();
const profileItems = ref([
    {
        label: 'Профиль',
        command: () => router.visit(route('profile.edit'))
    },
    {
        label: 'Написать в поддержку',
        command: () => window.open(usePage().props.supportLink, '_blank')
    },
    {
        label: 'Выход',
        command: () => useForm().post(route('logout'))
    }
]);

let $targetEl = null;

const op = ref();

const showBalance = ref(true);

const currentOverlayBalance = computed(() => {
  if (viewStore.isMerchantViewMode) {
    return walletProps.value?.merchant_balance || walletStatsForBalance.value?.totalAvailableBalances?.merchant?.primary || 0;
  } else if (viewStore.isTeamLeaderViewMode) {
    return walletStatsForBalance.value?.totalAvailableBalances?.teamleader?.primary || 0;
  } else if (viewStore.isTraderViewMode) {
    return walletProps.value?.trust_balance || walletStatsForBalance.value?.base?.trustAmount || 0;
  } else if (viewStore.isAdminViewMode) {
    return walletStatsForBalance.value?.base?.trustAmount || 
           walletStatsForBalance.value?.totalAvailableBalances?.merchant?.primary || 
           walletStatsForBalance.value?.totalAvailableBalances?.teamleader?.primary || 0;
  }
  return 0;
});

const assets = [
  {
    icon: '/images/tokens/usdt.svg',
    name: 'Tether USDt',
    symbol: 'USDT',
    amount: 0,
    usd: 0
  }
  // Можно добавить другие активы
];

const currentRouteName = ref(route().current()); // NEW: For active state tracking

// Инициализация макета
onMounted(() => {
    setInitialViewMode();
    initTheme();

    $targetEl = document.getElementById('mobile-sidebar');
})

// Переключение активности меню в зависимости от текущего маршрута
router.on('success', (event) => {
    setInitialViewMode();
    rates.value = usePage().props.data.rates;
    isImpersonated.value = usePage().props.auth.is_impersonated;
    menuData.value = usePage().props.menu;
    currentRouteName.value = route().current();
})

// Установка начального режима просмотра
function setInitialViewMode() {
    viewStore.setTraderViewMode();

    if (route().current('admin.*')) {
        viewStore.setAdminViewMode();
    }

    if (route().current('leader.*')) {
        viewStore.setTeamLeaderViewMode();
    }

    if (route().current('support.*')) {
        viewStore.setSupportViewMode();
    }

    //TODO это костыль для мерчантов
    if (route().current('profile.*')) {
        if (role.name === 'Super Admin') {
            viewStore.setAdminViewMode();
        } else if (role.name === 'Merchant') {
            viewStore.setMerchantViewMode();
        } else if (role.name === 'Trader') {
            viewStore.setTraderViewMode();
        } else if (role.name === 'Support') {
            viewStore.setSupportViewMode();
        }
    }
    if (route().current('merchant.*')) {
        viewStore.setMerchantViewMode();
    }
    if (route().current('merchants.*')) {
        viewStore.setMerchantViewMode();
    }
    if (route().current('integration.*')) {
        viewStore.setMerchantViewMode();
    }
    if (route().current('payments.*')) {
        viewStore.setMerchantViewMode();
    }
    if (route().current('payouts.*')) {
        viewStore.setMerchantViewMode();
    }
    if (route().current('payout-gateways.*')) {
        viewStore.setMerchantViewMode();
    }
}

// Инициализация темы — всегда тёмная
function initTheme() {
    document.documentElement.classList.add('dark');
    document.body.classList.add('dark');
    isDarkMode.value = true;
    localStorage.setItem('color-theme', 'dark');
    document.body.setAttribute('data-theme', 'aura-dark');
    document.querySelectorAll('.p-component').forEach(el => {
        el.classList.add('p-component-dark');
        el.classList.remove('p-component-light');
    });
}

// Переключение темы
function toggleTheme() {
    isDarkMode.value = !isDarkMode.value;
    if (isDarkMode.value) {
        document.documentElement.classList.add('dark');
        document.body.classList.add('dark');
        localStorage.setItem('color-theme', 'dark');
        // Применяем темную тему PrimeVue через класс body
        document.body.setAttribute('data-theme', 'aura-dark');
        
        // Обновление всех компонентов PrimeVue
        document.querySelectorAll('.p-component').forEach(el => {
            el.classList.add('p-component-dark');
            el.classList.remove('p-component-light');
        });
    } else {
        document.documentElement.classList.remove('dark');
        document.body.classList.remove('dark');
        localStorage.setItem('color-theme', 'light');
        // Применяем светлую тему PrimeVue через класс body
        document.body.setAttribute('data-theme', 'aura-light');
        
        // Принудительное обновление DOM для применения светлых стилей
        setTimeout(() => {
            const appRoot = document.querySelector('.app-layout');
            if (appRoot) {
                // Данный трюк заставляет браузер перерисовать DOM
                appRoot.style.display = 'none';
                appRoot.offsetHeight; // reflow
                appRoot.style.display = '';
            }
        }, 0);
    }
}

// Переключение сайдбара без использования flowbite
const toggleSidebar = () => {
    if ($targetEl) {
        const isVisible = $targetEl.classList.contains('transform-none');
        if (isVisible) {
            $targetEl.classList.remove('transform-none');
            $targetEl.classList.add('-translate-x-full');
        } else {
            $targetEl.classList.remove('-translate-x-full');
            $targetEl.classList.add('transform-none');
        }
    }
}

const leaveImpersonate = () => {
    useForm().post(route('impersonate.leave'));
};

const openDocs = () => {
    window.open('/docs', '_blank');
}

const showProfilePanel = (event) => profileOp.value.toggle(event);
const handleProfileAction = (action) => {
  profileOp.value.hide();
  if (action === 'profile') router.visit(route('profile.edit'));
  if (action === 'support') window.open(usePage().props.supportLink, '_blank');
  if (action === 'logout') useForm().post(route('logout'));
  if (action === 'vip') window.open(usePage().props.supportLink, '_blank');
};

// Для глобального модального окна вывода
const withdrawalModalBalanceType = ref(null);
const openGlobalWithdrawalModal = (type, params = {}) => {
    console.log('[AuthenticatedLayout] openGlobalWithdrawalModal called');
    console.log('[AuthenticatedLayout] type:', type);
    console.log('[AuthenticatedLayout] received params:', params);

    // Определяем targetUser:
    // 1. Если в params есть user (предполагается, что это user кошелька, переданный из компонента баланса)
    // 2. Иначе, используем аутентифицированного пользователя (user.value - это auth.user)
    const targetUser = params.user || user.value;

    console.log('[AuthenticatedLayout] targetUser determined as:', targetUser);
    // user.value здесь — это аутентифицированный админ/пользователь, если params.user не был предоставлен
    // console.log('[AuthenticatedLayout] auth user (for context):', user.value);

    if (!targetUser || !targetUser.id) {
        console.error('[AuthenticatedLayout] ERROR: targetUser is missing or invalid!', targetUser);
        return; 
    }

    withdrawalModalBalanceType.value = type;
    console.log('[AuthenticatedLayout] withdrawalModalBalanceType.value set to:', withdrawalModalBalanceType.value);
    
    // Передаем targetUser как 'user' в модальное окно.
    // Все остальные ключи из params также передаются.
    modalStore.openModal('withdrawal', { ...params, user: targetUser }); // params может содержать и другие ключи
    console.log('[AuthenticatedLayout] modalStore.openModal(\'withdrawal\') called with user:', targetUser, 'and final params for modal:', { ...params, user: targetUser });
};

provide('openGlobalWithdrawalModal', openGlobalWithdrawalModal);

const setBalanceTypeGlobal = (type) => { // Переименовано для ясности
    emit('setBalanceTypeGlobal', type);
};

// Function to handle navigation and close the mobile sidebar
const navigateAndCloseSidebar = (routeName) => {
  if (routeName) {
    sidebarVisible.value = false; // Close sidebar immediately
    router.visit(route(routeName));
    currentRouteName.value = routeName; // Update for active state
  }
};

// Function to handle bottom navigation clicks
const handleBottomNavClick = (routeName) => {
  if (routeName) {
    router.visit(route(routeName));
    currentRouteName.value = routeName; // Update for active state
  }
};

// Update currentRouteName on navigation events
router.on('navigate', () => {
  currentRouteName.value = route().current();
});

// Provide the function to child components (like AdminMenu, TraderMenu etc.)
provide('navigateAndCloseSidebar', navigateAndCloseSidebar);
</script>

<template>
    <div class="layout-wrapper">
        <!-- Основной макет без верхней панели -->
        <div class="app-layout">
            <!-- Боковая панель - видна только на десктопах -->
            <aside class="app-sidebar">
                <!-- Логотип для боковой панели -->
                <div class="sidebar-header">
                    <div class="flex items-center w-full">
                        <Link :href="route('dashboard')" class="sidebar-logo mr-2">
                            <img
                                :src="isDarkMode ? '/images/light.png' : '/images/dark.png'"
                                loading="lazy"
                                alt="Логотип"
                                class="max-w-[90px]"
                            />
                        </Link>
                        <!-- Добавляем отображение режима -->
                        <div class="flex items-center ml-auto pl-2 border-l border-gray-300 dark:border-gray-600 w-full">
                             <span class="text-sm font-semibold uppercase tracking-wider" 
                                  :class="{
                                      'text-sky-500 dark:text-sky-400': viewStore.isTraderViewMode,
                                      'text-sky-500 dark:text-sky-400': viewStore.isMerchantViewMode,
                                      'text-indigo-500 dark:text-indigo-400': viewStore.isTeamLeaderViewMode,
                                      'text-red-500 dark:text-red-400': viewStore.isAdminViewMode,
                                      'text-gray-500 dark:text-gray-400': viewStore.isSupportViewMode
                                  }">
                                <template v-if="viewStore.isTraderViewMode">Trader</template>
                                <template v-else-if="viewStore.isMerchantViewMode">Merchant</template>
                                <template v-else-if="viewStore.isTeamLeaderViewMode">TeamLead</template>
                                <template v-else-if="viewStore.isAdminViewMode">Admin</template>
                                <template v-else-if="viewStore.isSupportViewMode">Support</template>
                            </span>
                        </div>
                    </div>
                    <div v-if="isImpersonated" class="mt-2 w-full">
                        <Button
                            label="Выйти из Impersonate"
                            icon="pi pi-times-circle"
                            severity="danger"
                            outlined
                            size="small"
                            class="w-full"
                            @click="leaveImpersonate"
                        />
                    </div>
                </div>
                
                <!-- ViewModeSwitcher для боковой панели -->
                
                
                <!-- OnlineSwitcher для трейдеров -->
                <div v-if="viewStore.isTraderViewMode" class="px-4 py-3  ">
                    <OnlineSwitcher />
                </div>
                
                <!-- Меню -->
                <div class="sidebar-menu-container">
                    <TraderMenu v-if="viewStore.isTraderViewMode" :mobile="true" />
                    <MerchantMenu v-if="viewStore.isMerchantViewMode" :mobile="true" />
                    <TeamLeaderMenu v-if="viewStore.isTeamLeaderViewMode" :mobile="true" />
                    <AdminMenu v-if="viewStore.isAdminViewMode" :mobile="true" />
                    <SupportMenu v-if="viewStore.isSupportViewMode" :mobile="true" />
                </div>
                
                <!-- Футер боковой панели -->
                <div class="sidebar-footer">
                    <!-- Профиль пользователя -->
                    <button
                        class="w-full flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-muted transition-colors cursor-pointer text-left"
                        @click="showProfilePanel"
                    >
                        <div class="w-8 h-8 rounded-full flex items-center justify-center flex-shrink-0" style="background-color: color-mix(in srgb, var(--primary) 15%, transparent)">
                            <i class="pi pi-user text-sm" style="color: var(--primary)"></i>
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="text-sm font-medium text-foreground truncate">{{ user.email }}</div>
                            <div class="text-xs text-muted-foreground truncate">{{ user.name }}</div>
                        </div>
                        <i class="pi pi-ellipsis-v text-muted-foreground text-xs"></i>
                    </button>
                    <OverlayPanel
                        ref="profileOp"
                        appendTo="body"
                        class="p-3 rounded-xl shadow-xl bg-popover border border-border min-w-[260px] max-w-[320px]"
                    >
                        <div class="flex flex-row gap-2">
                            <div class="flex flex-col">
                                <div class="font-bold text-base text-foreground mb-0.5">{{ user.name }}</div>
                                <div class="text-xs text-muted-foreground mb-2">{{ user.email }}</div>
                            </div>
                            <div class="flex items-center gap-2 mb-2 text-xs">
                                <Tag v-if="user.is_vip" value="VIP" icon="pi pi-star-fill" severity="" size="small"></Tag>
                            </div>
                        </div>
                        <Divider class="my-1" />
                        <div v-if="userStore.isAdmin" class="mb-2 mx-2 my-2">
                            <ViewModeSwitcher />
                        </div>
                        <div>
                            <div class="px-4 py-2 cursor-pointer hover:bg-muted text-foreground text-sm rounded-lg transition"
                                 @click="handleProfileAction('profile')">Настройки</div>
                            <div class="px-4 py-2 cursor-pointer hover:bg-muted text-foreground text-sm rounded-lg transition"
                                 @click="handleProfileAction('support')">Написать в поддержку</div>
                            <div v-if="!user.is_vip"
                                 class="px-4 py-2 cursor-pointer hover:bg-primary/10 text-primary text-sm rounded-lg transition flex items-center gap-2"
                                 @click="handleProfileAction('vip')">
                                <i class="pi pi-star-fill"></i>
                                <span>Апгрейд до VIP</span>
                            </div>
                            <Divider class="my-1" />
                            <div class="px-4 py-2 cursor-pointer hover:bg-destructive/10 text-destructive text-sm rounded-lg transition"
                                 @click="handleProfileAction('logout')">Выйти</div>
                        </div>
                    </OverlayPanel>
                </div>
            </aside>
            
            <!-- Основной контент с хедером для кнопки профиля -->
            <div class="content-wrapper   ">
                <header class="content-header">
                    <!-- Бургер + логотип для мобильных -->
                    <div class="flex items-center gap-2 lg:hidden">
                        <Button
                            icon="pi pi-bars"
                            @click="sidebarVisible = true"
                            text
                            rounded
                            size="small"
                            class="text-foreground"
                        />
                        <Link :href="route('dashboard')">
                            <img
                                :src="isDarkMode ? '/images/light.png' : '/images/dark.png'"
                                loading="lazy"
                                alt="Логотип"
                                class="max-w-[90px]"
                            />
                        </Link>
                    </div>
                    <!-- Пустой элемент для заполнения пространства -->
                    <div class="flex-grow"></div>
                                           <!-- <div v-if="viewStore.isTraderViewMode" class=" mb-4  ">
                            <OnlineSwitcher />
                        </div> -->

                    <!-- Баланс для основного экрана -->
                    <div v-if="viewStore.isMerchantViewMode || viewStore.isTraderViewMode || viewStore.isTeamLeaderViewMode || viewStore.isAdminViewMode" 
                    class="balance-chip lg:flex lg:mr-4 lg:w-auto w-auto mr-2  ">
                        <Button
                            class="p-button p-component p-button-text user-profile-button menu-header-button p-component-dark flex items-center px-3 py-2"
                            @click="$event => op.toggle($event)"
                            aria-haspopup="true"
                            aria-controls="wallet_menu"
                        >
                        <div class="hidden md:block"> 
                            <i class="pi pi-wallet mr-0 lg:mr-3 wallet-iconer text-xl"></i>
                            </div>
                        <div class="flex items-start flex-col flex-start"> 
                            <span class="font-medium ">
                                {{ showBalance ?
                                    '$' + currentOverlayBalance.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})
                                    : '****' }}
                            </span>
                             <span class="font-normal text-xs hidden sm:block text-muted-foreground">Баланс кошелька</span>
                             <span class="font-normal text-xs block sm:hidden text-muted-foreground">Баланс</span>
                        </div>
                            
                            <i class="pi pi-angle-down ml-2 text-muted-foreground"></i>
                             
                        </Button>
                        <OverlayPanel ref="op" id="wallet_menu" class="p-3 rounded-xl shadow-xl bg-popover border border-border min-w-[320px] max-w-[360px]" style="z-index:9999;">
                            <div class="flex items-center justify-between mb-2">
                                <div>
                                    <div class="text-2xl font-bold text-foreground">
                                        {{ showBalance ?
                                            '$' + currentOverlayBalance.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})
                                            : '****' }}
                                    </div>
                                    <div class="text-muted-foreground text-sm">Общий баланс кошелька</div>
                                </div>
                                <Button
                                    :icon="showBalance ? 'pi pi-eye' : 'pi pi-eye-slash'"
                                    class="p-button-text p-button-rounded text-muted-foreground"
                                    size="small"
                                    @click="showBalance = !showBalance"
                                />
                            </div>
                            <div class="flex gap-2 mb-2">
                                <Button
                                    v-if="viewStore.isTraderViewMode"
                                    label="Пополнить"
                                    icon="pi pi-plus"
                                    class="flex-1 !bg-primary/12 !text-primary !border-none !shadow-none hover:!bg-primary/20"
                                    size="small"
                                    @click="op.hide(); openDepositModal()"
                                />
                                <!-- Кнопка Вывести для Трейдера -->
                                <!-- <Button
                                    v-if="viewStore.isTraderViewMode"
                                    label="Вывести"
                                    severity="help"
                                    icon="pi pi-arrow-down-right"
                                    class="flex-1"
                                    size="small"
                                    text
                                    @click.prevent="op.hide(); openGlobalWithdrawalModal('trust')"
                                /> -->
                                <!-- Кнопка Вывести для Тимлида -->
                                <Button
                                    v-if="viewStore.isTeamLeaderViewMode || (viewStore.isAdminViewMode && usePage().props.auth.role.name !== 'Merchant')" 
                                    label="Вывести"
                                    severity="help"
                                    icon="pi pi-arrow-down-right"
                                    class="flex-1"
                                    size="small"
                                    text
                                    @click.prevent="op.hide(); openGlobalWithdrawalModal('teamleader')"
                                />
                                <!-- Кнопка Вывести для Мерчанта -->
                                <Button
                                    v-if="viewStore.isMerchantViewMode || (viewStore.isAdminViewMode && usePage().props.auth.role.name === 'Merchant')"
                                    label="Вывести"
                                    severity="help"
                                    icon="pi pi-arrow-down-right"
                                    class="flex-1"
                                    size="small"
                                    text
                                    @click.prevent="op.hide(); openGlobalWithdrawalModal('merchant')"
                                />
                            </div>  
                            <!-- <Button label="История транзакций" icon="pi pi-history" 
                            class="w-full !bg-primary/12 !text-primary !border-none !shadow-none hover:!bg-primary/20 mb-2" 
                            size="small" @click="op.hide()" /> -->
                            <div class="text-muted-foreground text-xs mb-1 mt-2">Мои активы</div>
                            <div v-for="asset in assets" :key="asset.symbol" class="flex items-center gap-3 bg-muted rounded-lg px-3 py-2 mb-1">
                              
                                <!-- Внутри v-for="asset in assets" -->
                            <div v-if="asset.symbol === 'USDT'" class="w-8 h-8 rounded-full bg-white flex items-center justify-center">
                            <!-- SVG USDT -->
                            <svg width="28" height="28" viewBox="0 0 32 32" fill="none">
                                <circle cx="16" cy="16" r="16" fill="#26A17B"/>
                                <path d="M22.5 10.5h-13v2.1h5.1v4.1c-.7.1-1.4.2-2 .3v1.7c.6-.1 1.3-.2 2-.3v4.2h2.1v-4.2c.7-.1 1.4-.2 2-.3v-1.7c-.6.1-1.3.2-2 .3v-4.1h5.1v-2.1z" fill="#fff"/>
                            </svg>
                            </div>
                            <img v-else :src="asset.icon" alt="icon" class="w-8 h-8 rounded-full bg-white" />
                                <div class="flex-1">
                                    <div class="font-medium text-foreground">{{ asset.name }}</div>
                                    <div class="text-xs text-muted-foreground">{{ asset.symbol }}</div>
                                </div>
                                <div class="text-right">
                                    <div class="font-bold text-foreground"> {{ showBalance ?
                                              currentOverlayBalance.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})
                                            : '****' }} {{ asset.symbol }}</div>
                                    <div class="text-xs text-muted-foreground">{{ showBalance ?
                                            '$' + currentOverlayBalance.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})
                                            : '****' }}</div>
                                </div>
                            </div>
                        </OverlayPanel>
                        
                    </div>
                    
 

                </header>
                
                <main class="content-main">
                    <slot />
                </main>
            </div>
        </div>
        
        <!-- Мобильная панель профиля -->
        <Sidebar v-model:visible="sidebarVisible" position="left" class="mobile-sidebar" :showCloseIcon="false">
            <template #container="{ closeCallback }">
                <div class="mobile-sidebar-container">
                    <div class="sidebar-content">
                        <div class="flex justify-between items-center mb-3 pb-3 border-b border-gray-200 dark:border-gray-700">
                            <h2 class="text-base font-semibold tracking-tight">{{ usePage().props.appName }}</h2>
                            <Button icon="pi pi-times" @click="sidebarVisible = false" text rounded size="small" class="p-button-text" />
                        </div>
                        
                        <!-- Мобильное меню пользователя -->
                        <div class="user-info px-3 py-2.5 mb-3 rounded-lg">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-full flex items-center justify-center flex-shrink-0" style="background-color: color-mix(in srgb, var(--primary) 15%, transparent)">
                                    <i class="pi pi-user text-primary text-sm"></i>
                                </div>
                                <div class="min-w-0">
                                    <div class="font-medium text-sm truncate">{{ user.name }}</div>
                                    <div class="text-xs text-muted-foreground truncate">{{ user.email }}</div>
                                </div>
                            </div>
                            
                            <!-- Баланс для мобильной версии -->
                            <!-- <div v-if="viewStore.isMerchantViewMode || viewStore.isTraderViewMode" class="balance-chip mt-3">
                                <span class="flex items-center">
                                    <i class="pi pi-wallet mr-2"></i>
                                    <span class="font-medium">
                                        {{ (viewStore.isMerchantViewMode ? (usePage().props.data.wallet?.merchant_balance || 0) : (usePage().props.data.wallet?.trust_balance || 0)).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}) }}
                                        <span class="text-xs ml-1">USDT</span>
                                    </span>
                                </span>
                            </div>


                                    <div v-if="viewStore.isMerchantViewMode || viewStore.isTraderViewMode" class="balance-chip lg:flex lg:mr-4 lg:w-auto w-full">
                        <span class="flex items-center ">
                            <i class="pi pi-wallet mr-3 text-green-400 wallet-iconer"></i>
                            <span>
                                <div class="font-medium ">
                                    ${{ (viewStore.isMerchantViewMode ? (usePage().props.data.wallet?.merchant_balance || 0) : (usePage().props.data.wallet?.trust_balance || 0)).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}) }}
                                </div>
                                <div class="text-xs text-gray-400">
                                    Баланс кошелька
                                </div>
                            </span>
                        </span>
                    </div>
                     -->
                        </div>
                        
                        <!-- ViewModeSwitcher для мобильного -->
                        <div v-if="userStore.isAdmin" class="  mb-4  ">
                            <ViewModeSwitcher />
                        </div>
                        
                        <!-- OnlineSwitcher для мобильного -->
                        <div v-if="viewStore.isTraderViewMode" class=" mb-4  ">
                            <OnlineSwitcher />
                        </div>

                        <!-- Кнопка выхода из impersonate -->
                        <div v-if="isImpersonated" class="mb-4">
                            <Button
                                label="Выйти из Impersonate"
                                icon="pi pi-times-circle"
                                severity="danger"
                                outlined
                                class="w-full"
                                @click="leaveImpersonate"
                            />
                        </div>
                        
                        <!-- Отображение пунктов меню в зависимости от роли пользователя -->
                        <div class="mobile-menu-items mb-4">
                            <TraderMenu v-if="viewStore.isTraderViewMode" :mobile="true" />
                            <MerchantMenu v-if="viewStore.isMerchantViewMode" :mobile="true" />
                            <TeamLeaderMenu v-if="viewStore.isTeamLeaderViewMode" :mobile="true" />
                            <AdminMenu v-if="viewStore.isAdminViewMode" :mobile="true" />
                            <SupportMenu v-if="viewStore.isSupportViewMode" :mobile="true" />
                        </div>
                        
                        <!-- Курсы валют для мобильного -->
                        <div v-if="rates && rates.length > 0" class="  mb-4 ">
                            <div class="exchange-rates">
                                <div class="flex justify-between items-center">
                                    <h3 class="text-xs opacity-60 mb-2">Курс Tether TRC-20</h3>
                                    <Button 
                                        v-if="rates.length > 3" 
                                        :label="showAllRates ? 'Скрыть' : 'Все курсы'" 
                                        @click="showAllRates = !showAllRates" 
                                        text
                                        size="small"
                                        class="mb-2 p-0"
                                    />
                                </div>
                                <div v-for="(rate, index) in rates" :key="index" 
                                    v-show="index < 3 || showAllRates"
                                    class="flex justify-between py-1 text-sm">
                                    <span>{{ rate.buy_price }}</span>
                                    <span class="font-medium">{{ rate.code.toUpperCase() }}</span>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Кнопка выхода -->
                        <div class=" ">
                            <Button 
                                label="Выход" 
                                icon="pi pi-sign-out" 
                                severity="danger" 
                                outlined
                                class="w-full"
                                @click="() => useForm().post(route('logout'))"
                            />
                        </div>
                    </div>
                </div>
            </template>
        </Sidebar>
        
        <!-- Нижнее меню для мобильных устройств (скрыто) -->
        <div class="mobile-bottom-nav lg:hidden hidden">
            <div v-if="viewStore.isTraderViewMode" class="mobile-nav-container">
                <button @click="handleBottomNavClick('trader.main.index')" class="mobile-nav-item" :class="{'active': currentRouteName === 'trader.main.index' || route().current('trader.main.index')}">
                    <i class="pi pi-home"></i>
                    <span>Главная</span>
                </button>
                <button @click="handleBottomNavClick('payment-details.index')" class="mobile-nav-item" :class="{'active': currentRouteName === 'payment-details.index' || route().current('payment-details.* ')}">
                    <i class="pi pi-credit-card"></i>
                    <span>Реквизиты</span>
                    <span v-if="menuData?.activeDetails" class="mobile-menu-badge badge-success">{{ menuData.activeDetails }}</span>
                </button>
                <button @click="handleBottomNavClick('orders.index')" class="mobile-nav-item" :class="{'active': currentRouteName === 'orders.index' || route().current('orders.*')}">
                    <i class="pi pi-dollar"></i>
                    <span>Сделки</span>
                    <span v-if="menuData?.pendingOrdersCount" class="mobile-menu-badge badge-warning">{{ menuData.pendingOrdersCount }}</span>
                </button>
                <button @click="handleBottomNavClick('disputes.index')" class="mobile-nav-item" :class="{'active': currentRouteName === 'disputes.index' || route().current('disputes.*')}">
                    <i class="pi pi-exclamation-circle"></i>
                    <span>Споры</span>
                    <span v-if="menuData?.pendingDisputesCount" class="mobile-menu-badge badge-danger">{{ menuData.pendingDisputesCount }}</span>
                </button>
                <button @click="sidebarVisible = true" class="mobile-nav-item">
                    <i class="pi pi-bars"></i>
                    <span>Меню</span>
                </button>
            </div>
            
            <div v-if="viewStore.isMerchantViewMode" class="mobile-nav-container">
                <button @click="handleBottomNavClick('merchant.main.index')" class="mobile-nav-item" :class="{'active': currentRouteName === 'merchant.main.index' || route().current('merchant.main.index')}">
                    <i class="pi pi-home"></i>
                    <span>Главная</span>
                </button>
                <button @click="handleBottomNavClick('merchants.index')" class="mobile-nav-item" :class="{'active': currentRouteName === 'merchants.index' || route().current('merchants.*')}">
                    <i class="pi pi-box"></i>
                    <span>Мерчанты</span>
                </button>
                <button @click="handleBottomNavClick('payments.index')" class="mobile-nav-item" :class="{'active': currentRouteName === 'payments.index' || route().current('payments.*')}">
                    <i class="pi pi-dollar"></i>
                    <span>Платежи</span>
                </button>
                <button @click="handleBottomNavClick('merchant.finances.index')" class="mobile-nav-item" :class="{'active': currentRouteName === 'merchant.finances.index' || route().current('merchant.finances.*')}">
                    <i class="pi pi-wallet"></i>
                    <span>Финансы</span>
                </button>
                <button @click="sidebarVisible = true" class="mobile-nav-item">
                    <i class="pi pi-bars"></i>
                    <span>Меню</span>
                </button>
            </div>
            
            <div v-if="viewStore.isAdminViewMode" class="mobile-nav-container">
                <button @click="handleBottomNavClick('admin.main.index')" class="mobile-nav-item" :class="{'active': currentRouteName === 'admin.main.index' || route().current('admin.main.index')}">
                    <i class="pi pi-home"></i>
                    <span>Главная</span>
                </button>
                <button @click="handleBottomNavClick('admin.users.index')" class="mobile-nav-item" :class="{'active': currentRouteName === 'admin.users.index' || route().current('admin.users.*')}">
                    <i class="pi pi-users"></i>
                    <span>Пользователи</span>
                    <span v-if="menuData?.onlineUsers" class="mobile-menu-badge badge-info">{{ menuData.onlineUsers }}</span>
                </button>
                <button @click="handleBottomNavClick('admin.orders.index')" class="mobile-nav-item" :class="{'active': currentRouteName === 'admin.orders.index' || route().current('admin.orders.*')}">
                    <i class="pi pi-dollar"></i>
                    <span>Сделки</span>
                    <span v-if="menuData?.pendingOrdersCount" class="mobile-menu-badge badge-warning">{{ menuData.pendingOrdersCount }}</span>
                </button>
                <button @click="handleBottomNavClick('admin.disputes.index')" class="mobile-nav-item" :class="{'active': currentRouteName === 'admin.disputes.index' || route().current('admin.disputes.*')}">
                    <i class="pi pi-exclamation-circle"></i>
                    <span>Споры</span>
                    <span v-if="menuData?.pendingDisputesCount" class="mobile-menu-badge badge-danger">{{ menuData.pendingDisputesCount }}</span>
                </button>
                <button @click="sidebarVisible = true" class="mobile-nav-item">
                    <i class="pi pi-bars"></i>
                    <span>Меню</span>
                </button>
            </div>
            
            <div v-if="viewStore.isSupportViewMode" class="mobile-nav-container">
                <button @click="handleBottomNavClick('support.users.index')" class="mobile-nav-item" :class="{'active': currentRouteName === 'support.users.index' || route().current('support.users.*')}">
                    <i class="pi pi-users"></i>
                    <span>Пользователи</span>
                </button>
                <button @click="handleBottomNavClick('support.orders.index')" class="mobile-nav-item" :class="{'active': currentRouteName === 'support.orders.index' || route().current('support.orders.*')}">
                    <i class="pi pi-dollar"></i>
                    <span>Сделки</span>
                </button>
                <button @click="handleBottomNavClick('support.disputes.index')" class="mobile-nav-item" :class="{'active': currentRouteName === 'support.disputes.index' || route().current('support.disputes.*')}">
                    <i class="pi pi-exclamation-circle"></i>
                    <span>Споры</span>
                </button>
                <button @click="handleBottomNavClick('support.devices.index')" class="mobile-nav-item" :class="{'active': currentRouteName === 'support.devices.index' || route().current('support.devices.*')}">
                    <i class="pi pi-mobile"></i>
                    <span>Устройства</span>
                </button>
                <button @click="sidebarVisible = true" class="mobile-nav-item">
                    <i class="pi pi-bars"></i>
                    <span>Меню</span>
                </button>
            </div>
            
            <div v-if="viewStore.isTeamLeaderViewMode" class="mobile-nav-container">
                <button @click="handleBottomNavClick('leader.main.index')" class="mobile-nav-item" :class="{'active': currentRouteName === 'leader.main.index' || route().current('leader.main.index')}">
                    <i class="pi pi-home"></i>
                    <span>Главная</span>
                </button>
                <button @click="handleBottomNavClick('leader.promo-codes.index')" class="mobile-nav-item" :class="{'active': currentRouteName === 'leader.promo-codes.index' || route().current('leader.promo-codes.*')}">
                    <i class="pi pi-tag"></i>
                    <span>Промокоды</span>
                </button>
                <button @click="handleBottomNavClick('leader.referrals.index')" class="mobile-nav-item" :class="{'active': currentRouteName === 'leader.referrals.index' || route().current('leader.referrals.*')}">
                    <i class="pi pi-users"></i>
                    <span>Рефералы</span>
                </button>
                <button @click="sidebarVisible = true" class="mobile-nav-item">
                    <i class="pi pi-bars"></i>
                    <span>Меню</span>
                </button>
            </div>
        </div>
    </div>
           <DepositModalAuto
        v-if="showDepositModalAuto"
        :balanceType="'trust'"
        @closeModal="showDepositModalAuto = false"
      />
      <WithdrawalModal :balanceType="withdrawalModalBalanceType"/>
</template>

<style scoped>
/* Общие стили макета */
.layout-wrapper {
    min-height: 100vh;
    overflow-x: hidden;
    background-color: var(--background);
    position: relative;
}

/* Основной макет приложения */
.app-layout {
    display: flex;
    height: 100vh;
}

/* Стили для боковой панели */
.app-sidebar {
    width: 280px;
    background-color: var(--sidebar);
    display: flex;
    flex-direction: column;
    border-right: 1px solid var(--sidebar-border);
    height: 100vh;
    position: sticky;
    top: 0;
    overflow-y: auto;
}

.sidebar-header {
    padding: 0.6rem;
    border-bottom: 1px solid var(--surface-200, #e2e8f0);
    display: flex;
    flex-direction: column;
    align-items: center;
}

.dark .sidebar-header {
    @apply border-b-gray-700;
}

.sidebar-logo {
    height: 40px;
    display: flex;
    align-items: center;
}

.sidebar-menu-container {
    flex: 1;
    overflow-y: auto;
    padding: 0.5rem;
}

.sidebar-footer {
    padding: 1.5rem;
    border-top: 1px solid var(--surface-200, #e2e8f0);
}

.dark .sidebar-footer {
    @apply border-t-gray-700;
}

/* Стили для контента */
.content-wrapper {
    flex: 1;
    display: flex;
    flex-direction: column;
    max-height: 100vh;
    overflow-y: auto;
}

.content-header {
    position: sticky;
    top: 0;
    z-index: 50;
    display: flex;
    align-items: center;
    padding: 0.5rem 1rem;
    height: 64px;
    backdrop-filter: blur(16px);
    -webkit-backdrop-filter: blur(16px);
    background-color: color-mix(in srgb, var(--sidebar) 92%, transparent);
    border-bottom: 1px solid var(--sidebar-border);
    transition: box-shadow 0.2s ease;
}

.content-main {
    flex: 1;
    padding: 1.5rem;
    overflow-y: auto; 
        max-width: 1450px;
    margin: 0 auto;
    width: 100%;
}

/* Стили для кнопки профиля */
.user-profile {
    position: relative;
}

.user-profile-button {
    display: flex;
    align-items: center;
    background-color: transparent !important;
}

.user-profile {
    background-color: var(--card);
    border-radius: 5px;
}

.wallet-iconer {
    border-radius: 5px;
    padding: 0.8rem;
    background-color: color-mix(in srgb, var(--primary) 15%, transparent);
    color: var(--primary);
}

.wallet-iconer-avatar {
    border-radius: 5px;
    background-color: color-mix(in srgb, var(--primary) 15%, transparent);
    color: var(--primary);
}

.doler {
    @apply !bg-card;
    color: var(--muted-foreground) !important;
    padding: 1rem 1.8rem;
    border-radius: 5px;
}

 

/* Стили для баланса */
.balance-chip {
    background-color: var(--card);
    border-radius: var(--border-radius);
    font-weight: 500;
}

/* Мобильная боковая панель (left drawer) */
.mobile-sidebar-container {
    height: 100%;
    display: flex;
    flex-direction: column;
    overflow: hidden;
}

.mobile-sidebar :deep(.p-sidebar) {
    width: min(85vw, 320px) !important;
    height: 100dvh !important;
    max-height: 100dvh !important;
    border-radius: 0 !important;
}

.mobile-sidebar :deep(.p-sidebar-content) {
    padding: 0 !important;
    height: 100%;
    overflow: hidden;
}

.sidebar-content {
    padding: 1rem;
    height: 100%;
    overflow-y: auto;
    overscroll-behavior: contain;
    padding-bottom: 1.5rem;
}

.user-info {
    background-color: var(--surface-50, #f8fafc);
    border-radius: 10px;
}

.dark .user-info {
    @apply bg-gray-800;
}

.theme-toggle {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
}

/* Дополнительные настройки */
/* Переопределение переменных цвета для темной темы */
.dark {
    --surface-0: #18181b !important;
    --surface-50: #27272a !important;
    --surface-100: #3f3f46 !important;
    --surface-200: #52525b !important;
    --surface-300: #71717a !important;
    --surface-500: #d4d4d8 !important;
    --surface-900: #f8fafc !important;
    --surface-card: #18181b !important;
    --surface-ground: #09090b !important;

    --primary-color: #43E3F4 !important;
    --primary-color-text: #062830 !important;
}

/* Адаптивные стили */
@media (max-width: 1024px) {
    .app-sidebar {
        display: none;
    }
}

/* Стили для нижнего мобильного меню */
.mobile-bottom-nav {
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    backdrop-filter: blur(16px);
    -webkit-backdrop-filter: blur(16px);
    background-color: rgba(255, 255, 255, 0.92);
    border-top: 1px solid rgba(0, 0, 0, 0.06);
    z-index: 1001;
    height: 60px;
    padding: 0 10px;
}

.dark .mobile-bottom-nav {
    background-color: rgba(24, 24, 27, 0.92);
    border-top: 1px solid rgba(255, 255, 255, 0.05);
}

.mobile-nav-container {
    display: flex;
    justify-content: space-around;
    align-items: center;
    height: 100%;
}

.mobile-nav-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 10px 5px; /* Adjusted padding slightly for touch */
    text-align: center;
    color: var(--text-color);
    font-size: 0.8rem;
    position: relative;
    flex: 1; /* Ensure items take up space */
    cursor: pointer; /* Explicitly set cursor */
}

.mobile-menu-badge {
    position: absolute;
    top: 7px;
    right: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    min-width: 1.2rem;
    height: 1.2rem;
    font-size: 0.7rem;
    font-weight: 600;
    border-radius: 0.3rem;
    padding: 0 0.25rem;
}

.mobile-menu-badge.badge-info {
    @apply bg-badge-info/50 text-badge-info-text;
}

.mobile-menu-badge.badge-warning {
    @apply bg-badge-warning/25 text-badge-warning-text;
}

.mobile-menu-badge.badge-danger {
    @apply bg-badge-danger/25 text-badge-danger-text;
}

.dark .mobile-menu-badge.badge-info {
    @apply bg-badge-info-dark/50 text-badge-info-dark-text;
}

.mobile-nav-item i {
    font-size: 1.2rem;
    margin-bottom: 4px;
}

.mobile-nav-item.active {
    color: var(--primary-color, theme('colors.indigo.500'));
}

.mobile-nav-item.active::after {
    content: '';
    position: absolute;
    bottom: -12px;
    left: 50%;
    transform: translateX(-50%);
    width: 4px;
    height: 4px;
    border-radius: 50%;
    background-color: var(--primary-color, theme('colors.indigo.500'));
}

.dark .mobile-nav-item {
    @apply text-white/60;
}

.dark .mobile-nav-item.active {
    color: var(--primary-color, theme('colors.indigo.500'));
}


.dark .menu-header-button {
    border: none !important;
    transition: background-color 0.2s, color 0.2s, border-color 0.2s;
    border-radius: var(--border-radius) !important;
    background-color: transparent !important;
    color: var(--foreground) !important;
}

.dark .menu-header-button:enabled:hover {
   background: var(--surface-section2) !important;
     
}

.menu-header-button {
    border: none !important;
    transition: background-color 0.2s, color 0.2s, border-color 0.2s;
    border-radius: var(--border-radius) !important;
   
      color: var(--text-color) !important;
    border-color: var(--p-button-text-primary-color) !important;
}

.menu-header-button:enabled:hover {
   
     
}
 
.mobile-menu-badge.badge-success {
    background-color: var(--surface-ground);
    color: var(--p-button-text-primary-color);
}

/* .dark .mobile-menu-badge.badge-success {
    background-color: rgba(var(--primary-color-rgb), 0.2);
    color: var(--primary-color-lightest);
} */
</style>

<style>
.p-menu-overlay::before {
  content: '';
  display: block;
  position: absolute;
  top: -14px;
  left: 50%;
  transform: translateX(-50%);
  width: 0;
  height: 0;
  border-left: 13px solid transparent;
  border-right: 13px solid transparent;
  border-bottom: 13px solid theme('colors.gray.600');
  z-index: 1;
}
.p-menu-overlay::after {
  content: '';
  display: block;
  position: absolute;
  top: -12px;
  left: 50%;
  transform: translateX(-50%);
  width: 0;
  height: 0;
  border-left: 12px solid transparent;
  border-right: 12px solid transparent;
  border-bottom: 12px solid theme('colors.gray.800');
  z-index: 2;
}
body:not(.dark) .p-menu-overlay::after {
  border-bottom: 12px solid theme('colors.white');
}
body:not(.dark) .p-menu-overlay::before {
  border-bottom: 13px solid theme('colors.gray.200');
}
.p-popover-content {
padding: 0px !important; }

/* Стили для хвостика у кастомного меню карточки */
/*
.card-action-menu-overlay.p-menu-overlay::before {
  content: '';
  display: block;
  position: absolute;
  top: -14px; 
  right: 19px; 
  left: auto;
  transform: none;
  width: 0;
  height: 0;
  border-left: 13px solid transparent;
  border-right: 13px solid transparent;
  border-bottom: 13px solid theme('colors.gray.600');
  z-index: 1;
}

.card-action-menu-overlay.p-menu-overlay::after {
  content: '';
  display: block;
  position: absolute;
  top: -12px; 
  right: 20px; 
  left: auto;
  transform: none;
  width: 0;
  height: 0;
  border-left: 12px solid transparent;
  border-right: 12px solid transparent;
  border-bottom: 12px solid theme('colors.gray.800');
  z-index: 2;
}

body:not(.dark) .card-action-menu-overlay.p-menu-overlay::before {
  border-bottom-color: theme('colors.gray.200');
}

body:not(.dark) .card-action-menu-overlay.p-menu-overlay::after {
  border-bottom-color: theme('colors.white');
}
*/
/* Убрать стрелку у выпадающего меню PrimeVue */
.p-menu.p-component.p-menu-overlay::before,
.p-menu.p-component.p-menu-overlay::after {
    display: none !important;
}


</style>
