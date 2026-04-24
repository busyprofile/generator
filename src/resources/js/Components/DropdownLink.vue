<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    href: {
        type: String,
        required: true,
    },
});
</script>

<template>
    <Link
        :href="href"
        class="block w-full p-3 text-sm text-color hover:surface-100 dark:hover:surface-700 focus:outline-none focus:surface-100 dark:focus:surface-700 transition-colors duration-150"
    >
        <slot />
    </Link>
</template>
