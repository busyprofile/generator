<script setup>
const props = defineProps({
    hoverable: {
        type: Boolean,
        default: false
    },
});
</script>

<template>
    <tr
        class="bg-white dark:bg-gray-800 dark:border-gray-700"
        :class="hoverable ? 'hover:bg-gray-200/50 dark:hover:bg-gray-600 hover:cursor-pointer' : ''"
    >
        <slot/>
    </tr>
</template>

<style scoped>

</style>
