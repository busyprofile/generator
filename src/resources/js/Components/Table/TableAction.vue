<script setup>
import { inject, defineEmits } from "vue";

const closeMenu = inject("closeMenu");
const emit = defineEmits(["click"]);

const handleClick = (event) => {
    event.stopPropagation(); // Предотвращаем закрытие меню до выполнения действия
    emit("click"); // Вызываем переданный @click в родителе
    closeMenu(); // Закрываем меню
};
</script>

<template>
    <li class="p-menuitem">
        <button @click="handleClick" class="p-menuitem-link w-full">
            <slot />
        </button>
    </li>
</template>
