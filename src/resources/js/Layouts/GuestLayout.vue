<script setup>
import {onMounted} from "vue";
import {initFlowbite} from "flowbite";

onMounted(() => {
    initFlowbite();
    document.documentElement.classList.add('dark');
    localStorage.setItem('color-theme', 'dark');
})
</script>

<template>
    <div class="min-h-screen flex flex-col justify-center items-center bg-gray-50 dark:bg-gray-950 relative overflow-hidden">
        <!-- Декоративные фоновые элементы -->
        <div class="absolute inset-0 pointer-events-none overflow-hidden">
            <div class="absolute -top-32 -right-32 w-[500px] h-[500px] rounded-full bg-indigo-500/8 dark:bg-indigo-500/5 blur-3xl"></div>
            <div class="absolute -bottom-32 -left-32 w-[500px] h-[500px] rounded-full bg-sky-500/8 dark:bg-sky-500/5 blur-3xl"></div>
            <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[800px] h-[800px] rounded-full bg-violet-500/4 dark:bg-violet-500/3 blur-3xl"></div>
        </div>

        <div class="relative z-10 w-full sm:max-w-md px-4">
            <slot />
        </div>
    </div>
</template>
