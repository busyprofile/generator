<script setup>
import {onMounted, ref} from "vue";
import Button from "primevue/button";

const isDarkColorTheme = ref(false);

const switchThemeColorMode = () => {
    // if set via local storage previously
    if (localStorage.getItem('color-theme-payment')) {
        if (localStorage.getItem('color-theme-payment') === 'light') {
            document.documentElement.classList.add('dark');
            localStorage.setItem('color-theme-payment', 'dark');
            isDarkColorTheme.value = true;
        } else {
            document.documentElement.classList.remove('dark');
            localStorage.setItem('color-theme-payment', 'light');
            isDarkColorTheme.value = false;
        }

        // if NOT set via local storage previously
    } else {
        if (document.documentElement.classList.contains('dark')) {
            document.documentElement.classList.remove('dark');
            localStorage.setItem('color-theme-payment', 'light');
            isDarkColorTheme.value = false;
        } else {
            document.documentElement.classList.add('dark');
            localStorage.setItem('color-theme-payment', 'dark');
            isDarkColorTheme.value = true;
        }
    }
}

onMounted(() => {
    if (localStorage.getItem('color-theme-payment') === 'dark' || (!('color-theme-payment' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
        document.documentElement.classList.add('dark');
        localStorage.setItem('color-theme-payment', 'dark');
        isDarkColorTheme.value = true;
    } else {
        document.documentElement.classList.remove('dark')
        localStorage.setItem('color-theme-payment', 'light');
        isDarkColorTheme.value = false;
    }
})
</script>

<template>
    <Button 
        @click="switchThemeColorMode" 
        :icon="isDarkColorTheme ? 'pi pi-sun' : 'pi pi-moon'"
        severity="secondary"
        text
        rounded
        size="small"
        class="mr-2"
        :aria-label="isDarkColorTheme ? 'Переключить на светлую тему' : 'Переключить на темную тему'"
    />
</template>

<style scoped>

</style>
