<script setup>
import { Link, router, usePage } from "@inertiajs/vue3";
import { ref, computed, onMounted, watch, inject } from 'vue';
import Menu from 'primevue/menu';
import MobileCollapsibleMenu from '@/Layouts/Partials/MobileCollapsibleMenu.vue';

const props = defineProps({
    mobile: { type: Boolean, default: false }
});

const menu = ref(usePage().props.menu);
const currentRoute = ref(route().current());

// Inject the navigation function provided by AuthenticatedLayout
const navigateAndCloseSidebar = inject('navigateAndCloseSidebar');

// Обновляем данные меню и текущий маршрут при успешной навигации
router.on('success', (event) => {
    menu.value = usePage().props.menu;
    currentRoute.value = route().current();
});

// Обновляем текущий маршрут при изменении window.location
onMounted(() => {
    window.addEventListener('popstate', () => {
        currentRoute.value = route().current();
    });
});

// Заставляем меню обновляться при изменении маршрута
const menuKey = computed(() => currentRoute.value);

const isCurrentRoute = (pattern) => {
    return route().current(pattern);
};

const openExternal = (link) => {
    window.open('/' + link, '_blank');
};

// Определяем пункты меню для PrimeVue 
const items = computed(() => [
    {
        label: 'Главное',
        items: [
            {
                label: 'Главная',
                icon: 'pi pi-fw pi-th-large',
                command: () => {
                    navigateAndCloseSidebar('admin.main.index');
                },
                class: isCurrentRoute('admin.main.index') ? 'active-menu-item' : ''
            },
            {
                label: 'Пользователи',
                icon: 'pi pi-fw pi-user-plus',
                command: () => {
                    navigateAndCloseSidebar('admin.users.index');
                },
                shortcut: menu.value?.onlineUsers,
                shortcutClass: 'badge-info',
                class: isCurrentRoute('admin.users.*') ? 'active-menu-item' : ''
            },
            {
                label: 'Учет средств',
                icon: 'pi pi-fw pi-chart-bar',
                command: () => {
                    navigateAndCloseSidebar('admin.user-balances.index');
                },
                class: isCurrentRoute('admin.user-balances.*') ? 'active-menu-item' : ''
            }
        ]
    },
    {
        label: 'Валюты и платежи',
        items: [
            {
                label: 'Валюты',
                icon: 'pi pi-fw pi-dollar',
                command: () => {
                    navigateAndCloseSidebar('admin.currencies.index');
                },
                class: isCurrentRoute('admin.currencies.*') ? 'active-menu-item' : ''
            },
            {
                label: 'Платежные методы',
                icon: 'pi pi-fw pi-ticket',
                command: () => {
                    navigateAndCloseSidebar('admin.payment-gateways.index');
                },
                class: isCurrentRoute('admin.payment-gateways.*') ? 'active-menu-item' : ''
            },
            {
                label: 'Реквизиты',
                icon: 'pi pi-fw pi-wallet',
                command: () => {
                    navigateAndCloseSidebar('admin.payment-details.index');
                },
                shortcut: menu.value?.activeDetails,
                shortcutClass: 'badge-success',
                class: isCurrentRoute('admin.payment-details.*') ? 'active-menu-item' : ''
            },
            {
                label: 'Статистика реквизитов',
                icon: 'pi pi-fw pi-chart-bar',
                command: () => {
                    navigateAndCloseSidebar('admin.enabled-cards.index');
                },
                class: isCurrentRoute('admin.enabled-cards.*') ? 'active-menu-item' : ''
            },
            {
                label: 'Провайдеры реквизитов',
                icon: 'pi pi-fw pi-server',
                command: () => {
                    navigateAndCloseSidebar('admin.requisite-providers.index');
                },
                class: isCurrentRoute('admin.requisite-providers.*') ? 'active-menu-item' : ''
            }
        ]
    },
    {
        label: 'Транзакции',
        items: [
            {
                label: 'Сделки',
                icon: 'pi pi-fw pi-sync',
                command: () => {
                    navigateAndCloseSidebar('admin.orders.index');
                },
                shortcut: menu.value?.pendingOrdersCount,
                shortcutClass: 'badge-warning',
                class: isCurrentRoute('admin.orders.*') ? 'active-menu-item' : ''
            },
            {
                label: 'Споры',
                icon: 'pi pi-fw pi-ban',
                command: () => {
                    navigateAndCloseSidebar('admin.disputes.index');
                },
                shortcut: menu.value?.pendingDisputesCount,
                shortcutClass: 'badge-danger',
                class: isCurrentRoute('admin.disputes.*') ? 'active-menu-item' : ''
            },
            {
                label: 'Выплаты',
                icon: 'pi pi-fw pi-money-bill',
                command: () => {
                    navigateAndCloseSidebar('admin.payouts.index');
                },
                class: isCurrentRoute('admin.payouts.*') ? 'active-menu-item' : ''
            },
            {
                label: 'Депозиты средств',
                icon: 'pi pi-fw pi-inbox',
                command: () => {
                    navigateAndCloseSidebar('admin.deposits.index');
                },
                class: isCurrentRoute('admin.deposits.*') ? 'active-menu-item' : ''
            },
            {
                label: 'Вывод средств',
                icon: 'pi pi-fw pi-sign-out',
                command: () => {
                    navigateAndCloseSidebar('admin.withdrawals.index');
                },
                shortcut: menu.value?.pendingWithdrawals,
                shortcutClass: 'badge-warning',
                class: isCurrentRoute('admin.withdrawals.*') ? 'active-menu-item' : ''
            }
        ]
    },
    {
        label: 'Мерчанты',
        items: [
            {
                label: 'Мерчанты',
                icon: 'pi pi-fw pi-sitemap',
                command: () => {
                    navigateAndCloseSidebar('admin.merchants.index');
                },
                class: isCurrentRoute('admin.merchants.*') ? 'active-menu-item' : ''
            },
            {
                label: 'Категории мерчантов',
                icon: 'pi pi-fw pi-list',
                command: () => {
                    navigateAndCloseSidebar('admin.categories.index');
                },
                class: isCurrentRoute('admin.categories.*') ? 'active-menu-item' : ''
            }
        ]
    },
        {
        label: 'Трейдеры',
        items: [
            {
                label: 'Категории трейдеров',
                icon: 'pi pi-fw pi-tags',
                command: () => {
                    navigateAndCloseSidebar('admin.trader-categories.index');
                },
                class: isCurrentRoute('admin.trader-categories.*') ? 'active-menu-item' : ''
            },
            {
                label: 'Приоритеты категорий',
                icon: 'pi pi-fw pi-sort-amount-up',
                command: () => {
                    navigateAndCloseSidebar('admin.trader-category-priorities.index');
                },
                class: isCurrentRoute('admin.trader-category-priorities.*') ? 'active-menu-item' : ''
            }
        ]
    },
    {
        label: 'Провайдеры',
        items: [
            {
                label: 'Провайдеры',
                icon: 'pi pi-fw pi-briefcase',
                command: () => {
                    navigateAndCloseSidebar('admin.providers.index');
                },
                class: isCurrentRoute('admin.providers.*') ? 'active-menu-item' : ''
            },
            {
                label: 'Провайдер терминалы',
                icon: 'pi pi-fw pi-server',
                command: () => {
                    navigateAndCloseSidebar('admin.provider-terminals.index');
                },
                class: isCurrentRoute('admin.provider-terminals.*') ? 'active-menu-item' : ''
            },
            {
                label: 'Логи провайдеров',
                icon: 'pi pi-fw pi-code',
                command: () => {
                    navigateAndCloseSidebar('admin.provider-logs.index');
                },
                class: isCurrentRoute('admin.provider-logs.*') ? 'active-menu-item' : ''
            },
            {
                label: 'Колбеки провайдеров',
                icon: 'pi pi-fw pi-cloud-download',
                command: () => {
                    navigateAndCloseSidebar('admin.provider-callback-logs.index');
                },
                class: isCurrentRoute('admin.provider-callback-logs.*') ? 'active-menu-item' : ''
            }
        ]
    },
    {
        label: 'Система',
        items: [
            {
                label: 'Сообщения',
                icon: 'pi pi-fw pi-envelope',
                command: () => {
                    navigateAndCloseSidebar('admin.sms-logs.index');
                },
                class: isCurrentRoute('admin.sms-logs.*') ? 'active-menu-item' : ''
            },
            {
                label: 'Промокоды',
                icon: 'pi pi-fw pi-ticket',
                command: () => {
                    navigateAndCloseSidebar('admin.promo-codes.index');
                },
                class: isCurrentRoute('admin.promo-codes.*') ? 'active-menu-item' : ''
            },
            {
                label: 'API логи',
                icon: 'pi pi-fw pi-code',
                command: () => {
                    navigateAndCloseSidebar('admin.merchant-api-logs.index');
                },
                class: isCurrentRoute('admin.merchant-api-logs.*') ? 'active-menu-item' : ''
            },
            {
                label: 'Callback логи',
                icon: 'pi pi-fw pi-cloud-download',
                command: () => {
                    navigateAndCloseSidebar('admin.callback-logs.index');
                },
                class: isCurrentRoute('admin.callback-logs.*') ? 'active-menu-item' : ''
            },
            {
                label: 'Уведомления',
                icon: 'pi pi-fw pi-megaphone',
                command: () => {
                    navigateAndCloseSidebar('admin.notifications.index');
                },
                class: isCurrentRoute('admin.notifications.*') ? 'active-menu-item' : ''
            },
            {
                label: 'Настройки',
                icon: 'pi pi-fw pi-sliders-h',
                command: () => {
                    navigateAndCloseSidebar('admin.settings.index');
                },
                class: isCurrentRoute('admin.settings.*') ? 'active-menu-item' : ''
            },
            {
                label: 'Horizon',
                icon: 'pi pi-fw pi-compass',
                command: () => openExternal('horizon')
            },
            {
                label: 'Pulse',
                icon: 'pi pi-fw pi-heart-fill',
                command: () => openExternal('pulse')
            }
        ]
    }
]);
</script>

<template>
    <div class="admin-menu-container">
        <MobileCollapsibleMenu v-if="props.mobile" :items="items" />
        <Menu v-else :key="menuKey" :model="items" class="w-full admin-menu">
            <template #item="{ item, props: itemProps, hasSubmenu }">
                <a v-bind="itemProps.action" 
                   :class="[itemProps.action.class, item.class]"
                   @click="item.command && item.command()">
                    <span v-if="item.icon" :class="[itemProps.icon.class, item.icon]"></span>
                    <span :class="itemProps.label.class">{{ item.label }}</span>
                    <span v-if="item.shortcut" 
                          class="menu-badge" 
                          :class="item.shortcutClass">
                        {{ item.shortcut }}
                    </span>
                    <i v-if="hasSubmenu" :class="itemProps.submenuicon.class"></i>
                </a>
            </template>
        </Menu>
    </div>
</template>

<style scoped>
.admin-menu-container {
    width: 100%;
}

.admin-menu :deep(.p-menu) {
    width: 100%;
    border: none;
    background: transparent;
    padding: 0;
}

.admin-menu :deep(.p-menu-list) {
    padding: 0;
    margin: 0;
}

.admin-menu :deep(.p-menuitem) {
    margin-bottom: 0.25rem;
}

.admin-menu :deep(.p-menuitem-icon) {
    margin-right: 0.5rem;
    color: var(--primary-color);
}

.admin-menu :deep(.p-submenu-header) {
    background: transparent;
    color: var(--primary-color);
    font-weight: 700;
    border-radius: var(--border-radius);
    margin-top: 0.5rem;
}

.admin-menu :deep(.p-menuitem-link) {
    position: relative;
    border-radius: var(--border-radius);
    display: flex;
    align-items: center;
    padding: 0.75rem 1rem;
    color: var(--text-color);
    text-decoration: none;
    transition: background-color 0.2s;
    width: 100%;
}

.admin-menu :deep(.p-menuitem-link:hover) {
    background-color: var(--surface-hover);
}

.admin-menu :deep(.active-menu-item),
.admin-menu :deep(.p-menuitem-link.active-menu-item) {
    background-color: var(--primary-50) !important;
    color: var(--primary-color) !important;
    font-weight: 600;
}

.dark .admin-menu :deep(.active-menu-item),
.dark .admin-menu :deep(.p-menuitem-link.active-menu-item) {
    background-color: rgba(var(--primary-color-rgb), 0.2) !important; 
    color: var(--primary-color-lightest) !important;
}

.admin-menu :deep(.p-menuitem-active > .p-menuitem-link) {
    background: var(--primary-color-lightest);
    color: var(--primary-color);
}

.dark .admin-menu :deep(.p-menuitem-active > .p-menuitem-link) {
    background: rgba(var(--primary-color-rgb), 0.2);
    color: var(--primary-color-lightest);
}

.menu-badge {
    position: absolute;
    right: 0.5rem;
    top: 50%;
    transform: translateY(-50%);
    display: flex;
    align-items: center;
    justify-content: center;
    min-width: 1.5rem;
    height: 1.5rem;
    font-size: 0.75rem;
    font-weight: 600;
    border-radius: 0.3rem;
    padding: 0 0.25rem;
    background: var(--p-button-text-primary-active-background);
    color: var(--p-button-text-primary-color);
}

.menu-badge.badge-success {
    background-color: var(--p-button-text-primary-active-background);
}

.menu-badge.badge-warning {
    @apply bg-badge-warning/25 text-badge-warning-text;
}

.menu-badge.badge-danger {
    @apply bg-badge-danger/25 text-badge-danger-text;
}

 


.dark .menu-badge.badge-info {
    @apply bg-badge-info-dark/50 text-badge-info-dark-text;
}

.menu-badge.badge-info {
    @apply bg-badge-info/50 text-badge-info-text;
}
</style>
