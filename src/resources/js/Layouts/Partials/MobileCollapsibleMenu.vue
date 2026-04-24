<script setup>
import { ref, watch } from 'vue';

const props = defineProps({
    items: {
        type: Array,
        default: () => []
    }
});

const openSections = ref(new Set());

const initOpenSections = () => {
    const open = new Set();
    props.items?.forEach(section => {
        const hasActive = section.items?.some(
            item => typeof item.class === 'string' && item.class.includes('active-menu-item')
        );
        if (hasActive) open.add(section.label);
    });
    // Если ни одна секция не активна — открываем первую
    if (open.size === 0 && props.items?.length > 0) {
        open.add(props.items[0].label);
    }
    openSections.value = open;
};

watch(() => props.items, initOpenSections, { immediate: true, deep: true });

const toggle = (label) => {
    const s = new Set(openSections.value);
    s.has(label) ? s.delete(label) : s.add(label);
    openSections.value = s;
};
</script>

<template>
    <nav class="space-y-1">
        <div v-for="section in items" :key="section.label">
            <!-- Заголовок-папка -->
            <button
                @click="toggle(section.label)"
                class="w-full flex items-center justify-between px-3 py-2 rounded-lg transition-colors duration-150 select-none"
                :class="openSections.has(section.label)
                    ? 'text-primary bg-primary/8'
                    : 'text-gray-400 dark:text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-800/50'"
            >
                <span class="text-[0.68rem] font-bold uppercase tracking-widest leading-none">
                    {{ section.label }}
                </span>
                <i
                    class="pi pi-chevron-down text-[0.6rem] transition-transform duration-200"
                    :class="{ '-rotate-180': openSections.has(section.label) }"
                />
            </button>

            <!-- Элементы секции (коллапсируемые) -->
            <div
                class="overflow-hidden transition-[max-height,opacity] duration-200 ease-in-out"
                :class="openSections.has(section.label)
                    ? 'max-h-[600px] opacity-100'
                    : 'max-h-0 opacity-0'"
            >
                <div class="pl-2 pt-0.5 pb-1 space-y-0.5">
                    <a
                        v-for="item in (section.items || []).filter(i => i.visible !== false)"
                        :key="item.label"
                        @click.prevent="item.command && item.command()"
                        class="flex items-center gap-3 px-3 py-2.5 rounded-lg cursor-pointer transition-colors duration-150 relative"
                        :class="typeof item.class === 'string' && item.class.includes('active-menu-item')
                            ? 'bg-menu-active text-white'
                            : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800/60'"
                    >
                        <i
                            v-if="item.icon"
                            :class="[
                                item.icon,
                                typeof item.class === 'string' && item.class.includes('active-menu-item')
                                    ? 'text-white/80'
                                    : 'text-gray-400 dark:text-gray-500'
                            ]"
                            class="w-4 text-sm flex-shrink-0"
                        />
                        <span class="text-sm flex-1 leading-none">{{ item.label }}</span>
                        <span
                            v-if="item.shortcut"
                            class="menu-badge text-[0.65rem] font-semibold rounded px-1.5 py-0.5"
                            :class="item.shortcutClass"
                        >
                            {{ item.shortcut }}
                        </span>
                    </a>
                </div>
            </div>
        </div>
    </nav>
</template>

<style scoped>
.menu-badge.badge-warning {
    @apply bg-badge-warning/25 text-badge-warning-text;
}
.menu-badge.badge-danger {
    @apply bg-badge-danger/25 text-badge-danger-text;
}
.menu-badge.badge-info {
    @apply bg-badge-info/50 text-badge-info-text;
}
.menu-badge.badge-success {
    background-color: var(--surface-ground);
    color: var(--p-button-text-primary-color);
}

/* Переопределяем hover состояния для соответствия золотой теме */
button:hover {
    background-color: var(--menu-item-hover) !important;
}

a:hover {
    background-color: var(--menu-item-hover) !important;
}

/* Активные элементы меню */
.active-menu-item {
    background-color: var(--primary) !important;
    color: var(--primary-foreground) !important;
}

.active-menu-item i {
    color: var(--primary-foreground) !important;
}

/* Цвета заголовков секций */
button span {
    color: var(--text-color-secondary);
}

button:hover span {
    color: var(--menu-header-hover);
}
</style>
