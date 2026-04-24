<script setup>
import { Link, usePage, router } from "@inertiajs/vue3";
import { ref, computed, onMounted, watch, inject } from 'vue';
import Menu from 'primevue/menu';
import MobileCollapsibleMenu from '@/Layouts/Partials/MobileCollapsibleMenu.vue';

const props = defineProps({
    mobile: { type: Boolean, default: false }
});

const user = usePage().props.auth.user;
const currentRoute = ref(route().current());

// Inject the navigation function provided by AuthenticatedLayout
const navigateAndCloseSidebar = inject('navigateAndCloseSidebar');

// Обновляем текущий маршрут при любых изменениях маршрута
router.on('navigate', () => {
    setTimeout(() => {
        currentRoute.value = route().current();
        console.log('Route navigated:', currentRoute.value);
    }, 0);
});

router.on('success', () => {
    currentRoute.value = route().current();
    console.log('Route success:', currentRoute.value);
});

router.on('finish', () => {
    currentRoute.value = route().current();
    console.log('Route finish:', currentRoute.value);
});

// Обновляем текущий маршрут при изменении window.location
onMounted(() => {
    currentRoute.value = route().current();
    console.log('Initial route:', currentRoute.value);
    
    window.addEventListener('popstate', () => {
        setTimeout(() => {
            currentRoute.value = route().current();
            console.log('Popstate route:', currentRoute.value);
        }, 0);
    });
});

// Функция проверки активного маршрута с учетом текущего состояния
const isCurrentRoute = (pattern) => {
    // Используем computed для динамической проверки
    return computed(() => {
        return route().current(pattern);
    });
};

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
                        navigateAndCloseSidebar('merchant.main.index');
                    },
                    class: route().current('merchant.main.index') ? 'active-menu-item' : ''
                },
                {
                    label: 'Мерчанты',
                    icon: 'pi pi-fw pi-building',
                    command: () => {
                        navigateAndCloseSidebar('merchants.index');
                    },
                    class: route().current('merchants.*') ? 'active-menu-item' : ''
                }
            ]
        },
        {
            label: 'Финансы и платежи',
            items: [
                {
                    label: 'Платежи',
                    icon: 'pi pi-fw pi-wallet',
                    command: () => {
                        navigateAndCloseSidebar('payments.index');
                    },
                    class: route().current('payments.*') ? 'active-menu-item' : ''
                },
                {
                    label: 'Выплаты',
                    icon: 'pi pi-fw pi-money-bill',
                    command: () => {
                        navigateAndCloseSidebar('payouts.index');
                    },
                    class: route().current('payouts.*') ? 'active-menu-item' : '',
                    visible: user.payouts_enabled
                },
                {
                    label: 'Финансы',
                    icon: 'pi pi-fw pi-chart-line',
                    command: () => {
                        navigateAndCloseSidebar('merchant.finances.index');
                    },
                    class: route().current('merchant.finances.*') ? 'active-menu-item' : ''
                }
            ]
        },
        {
            label: 'Настройки',
            items: [
                {
                    label: 'Интеграция',
                    icon: 'pi pi-fw pi-cog',
                    command: () => {
                        navigateAndCloseSidebar('integration.index');
                    },
                    class: route().current('integration.*') ? 'active-menu-item' : ''
                }
            ]
        }
    ];
});

// Заставляем компонент меню полностью перерисовываться при изменении маршрута
const menuKey = computed(() => currentRoute.value);

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
    <div class="merchant-menu-container">
        <MobileCollapsibleMenu v-if="props.mobile" :items="filteredItems" />
        <Menu v-else :key="menuKey" :model="filteredItems" class="w-full merchant-menu" @item-click="item => item.command && item.command()" />
    </div>
</template>

<style scoped>
.merchant-menu-container {
    width: 100%;
}

.merchant-menu :deep(.p-menu) {
    width: 100%;
    border: none;
    background: transparent;
    padding: 0;
}

.merchant-menu :deep(.p-menu-list) {
    padding: 0;
    margin: 0;
}

.merchant-menu :deep(.p-menuitem) {
    margin-bottom: 0.25rem;
}

.merchant-menu :deep(.p-menuitem-icon) {
    margin-right: 0.5rem;
    color: var(--text-color-secondary);
}

.merchant-menu :deep(.p-submenu-header) {
    background: transparent;
    color: var(--text-color-secondary);
    font-weight: 600;
    margin-top: 0.5rem;
    padding: 0.5rem 1rem 0.25rem 1rem;
    font-size: 0.75rem;
    opacity: 0.8;
    letter-spacing: 0.5px;
}

.merchant-menu :deep(.p-menuitem-link) {
    display: flex;
    align-items: center;
    padding: 0.75rem 1rem;
    color: var(--text-color);
    text-decoration: none;
    transition: background-color 0.2s;
    border-radius: 0.375rem;
}

.merchant-menu :deep(.p-menuitem-link:hover) {
    background-color: var(--surface-hover);
}

.merchant-menu :deep(.p-menuitem-link .p-badge) {
    margin-left: auto;
}

.merchant-menu :deep(.active-menu-item),
.merchant-menu :deep(.p-menuitem-link.active-menu-item) {
    @apply !bg-menu-active !text-white;
    font-weight: 500;
}

.dark .merchant-menu :deep(.active-menu-item),
.dark .merchant-menu :deep(.p-menuitem-link.active-menu-item) {
    @apply !bg-menu-active !text-white;
}

.merchant-menu :deep(.p-menuitem-text) {
    font-size: 0.875rem;
}
</style>

 