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
        class="inline-flex align-items-center px-3 py-2 text-sm font-medium text-color hover:surface-100 dark:hover:surface-700 border-round transition-colors duration-150 focus:outline-none focus:surface-100 dark:focus:surface-600"
        :class="{ 'font-bold text-primary': active }"
    >
        <slot />
    </Link>
</template>
