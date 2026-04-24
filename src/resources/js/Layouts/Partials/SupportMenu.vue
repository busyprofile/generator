<script setup>
import { Link, usePage, router } from "@inertiajs/vue3";
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
            label: 'Пользователи',
            items: [
                {
                    label: 'Пользователи',
                    icon: 'pi pi-fw pi-users',
                    command: () => {
                        navigateAndCloseSidebar('support.users.index');
                    },
                    badge: menu.value?.onlineUsers,
                    class: route().current('support.users.*') ? 'active-menu-item' : ''
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
                        navigateAndCloseSidebar('support.orders.index');
                    },
                    badge: menu.value?.pendingOrdersCount,
                    class: route().current('support.orders.*') ? 'active-menu-item' : ''
                },
                {
                    label: 'Споры',
                    icon: 'pi pi-fw pi-exclamation-circle',
                    command: () => {
                        navigateAndCloseSidebar('support.disputes.index');
                    },
                    badge: menu.value?.pendingDisputesCount,
                    class: route().current('support.disputes.*') ? 'active-menu-item' : ''
                }
            ]
        },
        {
            label: 'Система',
            items: [
                {
                    label: 'Устройства',
                    icon: 'pi pi-fw pi-mobile',
                    command: () => {
                        navigateAndCloseSidebar('support.devices.index');
                    },
                    class: route().current('support.devices.*') ? 'active-menu-item' : ''
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
    <div class="support-menu-container">
        <MobileCollapsibleMenu v-if="props.mobile" :items="filteredItems" />
        <Menu v-else :key="menuKey" :model="filteredItems" class="w-full support-menu" @item-click="item => item.command && item.command()" />
    </div>
</template>

<style scoped>
.support-menu-container {
    width: 100%;
}

.support-menu :deep(.p-menu) {
    width: 100%;
    border: none;
    background: transparent;
    padding: 0;
}

.support-menu :deep(.p-menu-list) {
    padding: 0;
    margin: 0;
}

.support-menu :deep(.p-menuitem) {
    margin-bottom: 0.25rem;
}

.support-menu :deep(.p-menuitem-icon) {
    margin-right: 0.5rem;
    color: var(--text-color-secondary);
}

.support-menu :deep(.p-submenu-header) {
    background: transparent;
    color: var(--text-color-secondary);
    font-weight: 600;
    margin-top: 0.5rem;
    padding: 0.5rem 1rem 0.25rem 1rem;
    font-size: 0.75rem;
    opacity: 0.8;
    letter-spacing: 0.5px;
}

.support-menu :deep(.p-menuitem-link) {
    display: flex;
    align-items: center;
    padding: 0.75rem 1rem;
    color: var(--text-color);
    text-decoration: none;
    transition: background-color 0.2s;
    border-radius: 0.375rem;
}

.support-menu :deep(.p-menuitem-link:hover) {
    background-color: var(--surface-hover);
}

.support-menu :deep(.p-menuitem-link .p-badge) {
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

.support-menu :deep(.active-menu-item),
.support-menu :deep(.p-menuitem-link.active-menu-item) {
    @apply !bg-menu-active !text-white;
    font-weight: 500;
}

.dark .support-menu :deep(.active-menu-item),
.dark .support-menu :deep(.p-menuitem-link.active-menu-item) {
    @apply !bg-menu-active !text-white;
}

.support-menu :deep(.p-menuitem-text) {
    font-size: 0.875rem;
}
</style> 