<script setup>
import { ref, onMounted, onUnmounted, provide, nextTick } from "vue";

const isOpen = ref(false);
const dropdown = ref(null);
const button = ref(null);
const dropdownPosition = ref({ top: 0, left: 0, width: 0 });

const toggleDropdown = async () => {
    isOpen.value = !isOpen.value;

    if (isOpen.value) {
        await nextTick(); // Ждём ререндер перед получением координат

        if (button.value && dropdown.value) {
            const rect = button.value.getBoundingClientRect();
            dropdownPosition.value = {
                top: rect.bottom + window.scrollY + 4, // Отступ 4px
                left: rect.left + window.scrollX,
                width: rect.width,
            };
        }
    }
};

const closeDropdown = () => {
    isOpen.value = false;
};

// Передаём `closeMenu` дочерним компонентам
provide("closeMenu", closeDropdown);

// Закрытие при клике вне меню
const handleClickOutside = (event) => {
    if (
        dropdown.value &&
        !dropdown.value.contains(event.target) &&
        button.value &&
        !button.value.contains(event.target)
    ) {
        isOpen.value = false;
    }
};

onMounted(() => {
    document.addEventListener("click", handleClickOutside);
});

onUnmounted(() => {
    document.removeEventListener("click", handleClickOutside);
});
</script>

<template>
    <div class="relative inline-block text-left">
        <button
            @click="toggleDropdown"
            ref="button"
            class="p-2 text-gray-500 hover:text-gray-700 rounded-full focus:outline-none focus:ring-2 focus:ring-gray-300"
        >
            <svg class="w-[24px] h-[24px] text-gray-800 dark:text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                <path stroke="currentColor" stroke-linecap="round" stroke-width="2" d="M12 6h.01M12 12h.01M12 18h.01"/>
            </svg>
        </button>

        <!-- Используем teleport, чтобы меню было вне ограничений таблицы -->
        <teleport to="body">
            <div
                v-if="isOpen"
                ref="dropdown"
                class="absolute z-50 bg-white border border-gray-200 rounded-lg shadow-lg"
                :style="{ top: dropdownPosition.top + 'px', left: dropdownPosition.left + 'px' }"
            >
                <ul class="py-2 text-gray-700">
                    <slot />
                </ul>
            </div>
        </teleport>
    </div>
</template>

