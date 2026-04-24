<script setup>
import { Link, usePage, router } from "@inertiajs/vue3";
import { ref, computed, onMounted, watch, inject } from 'vue';
import Menu from 'primevue/menu';
import MobileCollapsibleMenu from '@/Layouts/Partials/MobileCollapsibleMenu.vue';

const props = defineProps({
    mobile: { type: Boolean, default: false }
});

const user = usePage().props.auth.user;
const menu = ref(usePage().props.menu);
const currentRoute = ref(route().current());

// Inject the navigation function provided by AuthenticatedLayout
const navigateAndCloseSidebar = inject('navigateAndCloseSidebar');

// Обновляем текущий маршрут при любых изменениях маршрута
router.on('navigate', () => {
    setTimeout(() => {
        currentRoute.value = route().current();
    }, 0);
});

router.on('success', (event) => {
    menu.value = usePage().props.menu;
    currentRoute.value = route().current();
});

router.on('finish', () => {
    currentRoute.value = route().current();
});

// Обновляем текущий маршрут при изменении window.location
onMounted(() => {
    currentRoute.value = route().current();
    
    window.addEventListener('popstate', () => {
        setTimeout(() => {
            currentRoute.value = route().current();
        }, 0);
    });
});

// Заставляем компонент меню полностью перерисовываться при изменении маршрута
const menuKey = computed(() => currentRoute.value);

// Определяем пункты меню для PrimeVue с разделами
const items = computed(() => {
    // Используем currentRoute для пересчета computed при изменении маршрута
    const cr = currentRoute.value;
    
    return [
        {
            label: 'Главное',
            items: [
                {
                    label: 'Главная',
                    icon: 'pi pi-fw pi-home',
                    command: () => {
                        navigateAndCloseSidebar('trader.main.index');
                    },
                    class: route().current('trader.main.index') ? 'active-menu-item' : ''
                },
                {
                    label: 'Реквизиты',
                    icon: 'pi pi-fw pi-id-card',
                    command: () => {
                        navigateAndCloseSidebar('payment-details.index');
                    },
                    shortcut: menu.value?.activeDetails,
                    class: route().current('payment-details.*') ? 'active-menu-item' : ''
                },
                {
                    label: 'Белые треугольники',
                    icon: 'pi pi-fw pi-th-large',
                    command: () => {
                        navigateAndCloseSidebar('trader.white-triangles.index');
                    },
                    class: route().current('trader.white-triangles.*') ? 'active-menu-item' : ''
                }
            ]
        },
        {
            label: 'Операции',
            items: [
                {
                    label: 'Сделки',
                    icon: 'pi pi-fw pi-sync',
                    command: () => {
                        navigateAndCloseSidebar('orders.index');
                    },
                    shortcut: menu.value?.pendingOrdersCount,
                    shortcutClass: 'badge-warning',
                    class: route().current('orders.*') ? 'active-menu-item' : ''
                },
                {
                    label: 'Споры',
                    icon: 'pi pi-fw pi-exclamation-circle',
                    command: () => {
                        navigateAndCloseSidebar('disputes.index');
                    },
                    shortcut: menu.value?.pendingDisputesCount,
                    shortcutClass: 'badge-danger',
                    class: route().current('disputes.*') ? 'active-menu-item' : ''
                },
                {
                    label: 'Выплаты',
                    icon: 'pi pi-fw pi-money-bill',
                    command: () => {
                        navigateAndCloseSidebar('payouts.index');
                    },
                    class: route().current('payouts.*') ? 'active-menu-item' : '',
                    visible: user.payouts_enabled
                }
            ]
        },
        {
            label: 'Финансы и статистика',
            items: [
                {
                    label: 'Финансы',
                    icon: 'pi pi-fw pi-wallet',
                    command: () => {
                        navigateAndCloseSidebar('wallet.index');
                    },
                    class: route().current('wallet.*') ? 'active-menu-item' : ''
                },
                {
                    label: 'Статистика',
                    icon: 'pi pi-fw pi-chart-line',
                    command: () => {
                        navigateAndCloseSidebar('trader.statistics.index');
                    },
                    class: route().current('trader.statistics.*') ? 'active-menu-item' : ''
                }
            ]
        },
        {
            label: 'Система',
            items: [
                {
                    label: 'Уведомления',
                    icon: 'pi pi-fw pi-bell',
                    command: () => {
                        navigateAndCloseSidebar('notifications.index');
                    },
                    class: route().current('notifications.*') ? 'active-menu-item' : ''
                },
                {
                    label: 'Сообщения',
                    icon: 'pi pi-fw pi-envelope',
                    command: () => {
                        navigateAndCloseSidebar('sms-logs.index');
                    },
                    class: route().current('sms-logs.*') ? 'active-menu-item' : ''
                },
                // {
                //     label: 'Настройки',
                //     icon: 'pi pi-fw pi-cog',
                //     command: () => {
                //         router.visit(route('trader.settings.index'));
                //     },
                //     class: route().current('trader.settings.*') ? 'active-menu-item' : ''
                // },
                {
                    label: 'Устройства',
                    icon: 'pi pi-fw pi-mobile',
                    command: () => {
                        navigateAndCloseSidebar('trader.devices.index');
                    },
                    class: route().current('trader.devices.*') ? 'active-menu-item' : ''
                }
            ]
        }
    ];
});

// Фильтруем видимые пункты меню в разделах
const filteredItems = computed(() => {
    return items.value.map(section => {
        const newSection = {...section};
        if (newSection.items) {
            newSection.items = newSection.items.filter(item => item.visible !== false);
        }
        return newSection.items && newSection.items.length > 0 ? newSection : null;
    }).filter(Boolean);
});
</script>

<template>
    <div class="trader-menu-container">
        <MobileCollapsibleMenu v-if="props.mobile" :items="filteredItems" />
        <Menu v-else :key="menuKey" :model="filteredItems" class="w-full trader-menu">
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
.trader-menu-container {
    width: 100%;
}

.trader-menu :deep(.p-menu) {
    width: 100%;
    border: none;
    background: transparent;
    padding: 0;
}

.trader-menu :deep(.p-menu-list) {
    padding: 0;
    margin: 0;
}

.trader-menu :deep(.p-menuitem) {
    margin-bottom: 0.25rem;
}

.trader-menu :deep(.p-menuitem-icon) {
    margin-right: 0.5rem;
    color: var(--text-color-secondary);
}

.trader-menu :deep(.p-submenu-header) {
    background: transparent;
    color: var(--text-color-secondary);
    font-weight: 600;
    margin-top: 0.5rem;
    padding: 0.5rem 1rem 0.25rem 1rem;
    font-size: 0.75rem;
    opacity: 0.8;
    letter-spacing: 0.5px;
}

.trader-menu :deep(.p-menuitem-link) {
    position: relative;
    display: flex;
    align-items: center;
    padding: 0.75rem 1rem;
    color: var(--text-color);
    text-decoration: none;
    transition: background-color 0.2s;
    border-radius: 0.375rem;
    width: 100%;
}

.trader-menu :deep(.p-menuitem-link:hover) {
    background-color: var(--surface-hover);
}

.trader-menu :deep(.p-menuitem-link .p-badge) {
    margin-left: auto;
    display: flex;
    align-items: center;
    justify-content: center;
    min-width: 1.5rem;
    height: 1.5rem;
    font-size: 0.75rem;
    font-weight: 600;
    border-radius: 50%;
}

.trader-menu :deep(.active-menu-item),
.trader-menu :deep(.p-menuitem-link.active-menu-item) {
    @apply !bg-menu-active !text-white;
    font-weight: 500;
}

.dark .trader-menu :deep(.active-menu-item),
.dark .trader-menu :deep(.p-menuitem-link.active-menu-item) {
    @apply !bg-menu-active !text-white;
}

.trader-menu :deep(.p-menuitem-text) {
    font-size: 0.875rem;
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
    background: var(--p-button-text-primary-active-background) !important;
    color: var(--p-button-text-primary-color) !important;
}

.menu-badge.badge-warning {
    background-color: color-mix(in srgb, var(--primary-color) 15%, transparent);
    color: var(--primary-color);
}

.menu-badge.badge-danger {
    @apply bg-red-500;
}

.menu-badge.badge-info {
    background-color: color-mix(in srgb, var(--primary-color) 15%, transparent);
    color: var(--primary-color);
}
</style>
