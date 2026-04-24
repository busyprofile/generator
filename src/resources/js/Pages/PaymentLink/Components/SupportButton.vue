<script setup>
const props = defineProps({
    support_link: {
        type: String,
    },
});

const openSupport = () => {
    window.open(props.support_link, '_blank');
};
</script>

<template>
    <button
        v-show="support_link"
        @click.prevent="openSupport"
        type="button"
        class="  text-center flex items-center justify-center text-gray-900 bg-white border border-gray-300 focus:outline-none hover:bg-gray-100 focus:ring-4 focus:ring-gray-100 font-medium rounded-xl text-sm px-3 py-1.5 dark:bg-gray-800 dark:text-white dark:border-gray-600 dark:hover:bg-gray-700 dark:hover:border-gray-600 dark:focus:ring-gray-700 border-gray-200 dark:border-gray-700"
    >
       
        Поддержка
    </button>
</template>

<style scoped>
 
</style>
