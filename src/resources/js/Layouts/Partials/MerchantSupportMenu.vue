<script setup>
import {Link, usePage} from "@inertiajs/vue3";

const user = usePage().props.auth.user;
</script>

<template>
    <ul class="space-y-2 font-medium">
        <li>
            <Link preserve-scroll :href="route('merchant-support.payments.index')" :class="{'bg-gray-100 dark:bg-gray-700': route().current('merchant-support.payments.*')}" class="flex items-center p-2 text-gray-900 rounded-xl  dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700 group">
                <svg class="flex-shrink-0 w-5 h-5 text-gray-500 transition duration-75 dark:text-gray-400 group-hover:text-gray-900 dark:group-hover:text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 17.345a4.76 4.76 0 0 0 2.558 1.618c2.274.589 4.512-.446 4.999-2.31.487-1.866-1.273-3.9-3.546-4.49-2.273-.59-4.034-2.623-3.547-4.488.486-1.865 2.724-2.899 4.998-2.31.982.236 1.87.793 2.538 1.592m-3.879 12.171V21m0-18v2.2"/>
                </svg>
                <span class="ms-3">Платежи</span>
            </Link>
        </li>
        <li>
            <Link preserve-scroll :href="route('merchant-support.integration.index')" :class="{'bg-gray-100 dark:bg-gray-700': route().current('merchant-support.integration.*')}" class="flex items-center p-2 text-gray-900 rounded-xl  dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700 group">
                <svg class="flex-shrink-0 w-5 h-5 text-gray-500 transition duration-75 dark:text-gray-400 group-hover:text-gray-900 dark:group-hover:text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 13V4M7 14H5a1 1 0 0 0-1 1v4a1 1 0 0 0 1 1h14a1 1 0 0 0 1-1v-4a1 1 0 0 0-1-1h-2m-1-5-4 5-4-5m9 8h.01"/>
                </svg>

                <span class="ms-3">Интеграция</span>
            </Link>
        </li>
    </ul>
</template> 