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
            label: 'Главное',
            items: [
                {
                    label: 'Главная',
                    icon: 'pi pi-fw pi-home',
                    command: () => {
                        navigateAndCloseSidebar('leader.main.index');
                    },
                    class: route().current('leader.main.index') ? 'active-menu-item' : ''
                }
            ]
        },
        {
            label: 'Финансы',
            items: [
                {
                    label: 'Финансы',
                    icon: 'pi pi-fw pi-ticket',
                    command: () => {
                        navigateAndCloseSidebar('leader.finances.index');
                    },
                    class: route().current('leader.finances.*') ? 'active-menu-item' : ''
                }
            ]
        },
        {
            label: 'Маркетинг',
            items: [
                // {
                //     label: 'Промокоды',
                //     icon: 'pi pi-fw pi-ticket',
                //     command: () => {
                //         navigateAndCloseSidebar('leader.promo-codes.index');
                //     },
                //     class: route().current('leader.promo-codes.*') ? 'active-menu-item' : ''
                // },
                {
                    label: 'Рефералы',
                    icon: 'pi pi-fw pi-users',
                    command: () => {
                        navigateAndCloseSidebar('leader.referrals.index');
                    },
                    class: route().current('leader.referrals.*') ? 'active-menu-item' : ''
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
    <div class="leader-menu-container">
        <MobileCollapsibleMenu v-if="props.mobile" :items="filteredItems" />
        <Menu v-else :key="menuKey" :model="filteredItems" class="w-full leader-menu" @item-click="item => item.command && item.command()" />
    </div>
</template>

<style scoped>
.leader-menu-container {
    width: 100%;
}

.leader-menu :deep(.p-menu) {
    width: 100%;
    border: none;
    background: transparent;
    padding: 0;
}

.leader-menu :deep(.p-menu-list) {
    padding: 0;
    margin: 0;
}

.leader-menu :deep(.p-menuitem) {
    margin-bottom: 0.25rem;
}

.leader-menu :deep(.p-menuitem-icon) {
    margin-right: 0.5rem;
    color: var(--text-color-secondary);
}

.leader-menu :deep(.p-submenu-header) {
    background: transparent;
    color: var(--text-color-secondary);
    font-weight: 600;
    margin-top: 0.5rem;
    padding: 0.5rem 1rem 0.25rem 1rem;
    font-size: 0.75rem;
    opacity: 0.8;
    letter-spacing: 0.5px;
}

.leader-menu :deep(.p-menuitem-link) {
    display: flex;
    align-items: center;
    padding: 0.75rem 1rem;
    color: var(--text-color);
    text-decoration: none;
    transition: background-color 0.2s;
    border-radius: 0.375rem;
}

.leader-menu :deep(.p-menuitem-link:hover) {
    background-color: var(--surface-hover);
}

.leader-menu :deep(.p-menuitem-link .p-badge) {
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

.leader-menu :deep(.active-menu-item),
.leader-menu :deep(.p-menuitem-link.active-menu-item) {
    @apply !bg-menu-active !text-white;
    font-weight: 500;
}

.dark .leader-menu :deep(.active-menu-item),
.dark .leader-menu :deep(.p-menuitem-link.active-menu-item) {
    @apply !bg-menu-active !text-white;
}

.leader-menu :deep(.p-menuitem-text) {
    font-size: 0.875rem;
}
</style>
