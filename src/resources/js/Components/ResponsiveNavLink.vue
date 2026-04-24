<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    href: {
        type: String,
        required: true,
    },
    active: {
        type: Boolean,
    },
});
</script>

<template>
    <Link 
        :href="href" 
        class="block w-full p-3 border-round text-color hover:surface-100 dark:hover:surface-700 focus:outline-none focus:surface-200 dark:focus:surface-600 transition-colors duration-150"
        :class="{ 'bg-surface-100 dark:bg-surface-700 font-semibold': active }"
    >
        <slot />
    </Link>
</template>
