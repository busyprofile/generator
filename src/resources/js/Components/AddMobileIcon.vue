<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import {router} from "@inertiajs/vue3";
import Button from 'primevue/button';

// --- Responsive Button Size Logic ---
const screenWidth = ref(window.innerWidth);

const updateScreenWidth = () => {
    screenWidth.value = window.innerWidth;
};

onMounted(() => {
    window.addEventListener('resize', updateScreenWidth);
});

onUnmounted(() => {
    window.removeEventListener('resize', updateScreenWidth);
});

// Computed property for button size based on screen width (md breakpoint: 768px)
const buttonSize = computed(() => {
    return screenWidth.value < 768 ? 'small' : null;
});
// --- End Responsive Button Size Logic ---
</script>

<template>
    <Button
        icon="pi pi-plus"
        severity="success"
        label="Добавить"
        :size="buttonSize"
    >
        <!-- Slot is kept empty as per original component, tooltip provides the text -->
    </Button>
</template>

<style scoped>

</style>
