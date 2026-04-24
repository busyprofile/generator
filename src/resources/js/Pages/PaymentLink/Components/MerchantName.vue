<script setup>
const props = defineProps({
    name: {
        type: String,
    },
});
</script>

<template>
    <h2 class="text-sm font-medium text-gray-900 dark:text-white sm:text-sm">{{ name }}</h2>
</template>

<style scoped>

</style>
