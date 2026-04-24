<script setup>
import { ref, computed } from 'vue';

const props = defineProps({
    img_path: {
        type: String,
    },
    name: {
        type: String,
        default: null
    }
});

const uid = computed(() => Math.random().toString(36).substring(2, 9));
</script>

<template>
    <div>
        <img v-if="img_path"
             :src="img_path"
             class=""
             :data-tooltip-target="name ? `tooltip-gateway-logo-${uid}` : null" />
        <svg v-else
             aria-hidden="true"
             xmlns="http://www.w3.org/2000/svg"
             fill="none"
             viewBox="0 0 24 24"
             :data-tooltip-target="name ? `tooltip-gateway-logo-${uid}` : null">
            <path stroke="currentColor" stroke-linecap="round" stroke-width="2" d="M3 21h18M4 18h16M6 10v8m4-8v8m4-8v8m4-8v8M4 9.5v-.955a1 1 0 0 1 .458-.84l7-4.52a1 1 0 0 1 1.084 0l7 4.52a1 1 0 0 1 .458.84V9.5a.5.5 0 0 1-.5.5h-15a.5.5 0 0 1-.5-.5Z"/>
        </svg>

        <div v-if="name"
             :id="`tooltip-gateway-logo-${uid}`"
             role="tooltip"
             class="absolute z-10 invisible inline-block px-3 py-2 text-sm font-medium text-white transition-opacity duration-300 bg-gray-900 rounded-xl shadow-sm opacity-0 tooltip dark:bg-gray-700">
            {{ name }}
            <div class="absolute w-2 h-2 bg-inherit -bottom-1 left-1/2 -translate-x-1/2 rotate-45" data-popper-arrow></div>
        </div>
    </div>
</template>

<style scoped>
</style>