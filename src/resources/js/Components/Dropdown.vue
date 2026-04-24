<script setup>
import { ref } from 'vue';
import OverlayPanel from 'primevue/overlaypanel';

const op = ref();

const toggle = (event) => {
    op.value.toggle(event);
};
</script>

<template>
    <div class="relative">
        <div @click="toggle">
            <slot name="trigger" />
        </div>

        <OverlayPanel ref="op">
            <slot name="content" />
        </OverlayPanel>
    </div>
</template>
