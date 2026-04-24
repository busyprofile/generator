<template>
    <Paginator
        :template="paginatorTemplate"
        :rows="perPage"
        :totalRecords="totalItems"
        :first="first"
        :pageLinkSize="sliceLength || 5"
        @page="onPageChange"
        :currentPageReportTemplate="layout === 'table' ? 'Showing {first} to {last} of {totalRecords} entries' : '({currentPage} of {totalPages})'"
        class="border dark:border-gray-700 rounded-xl border-gray-200"
        :pt="{
            root: { class: 'py-0 px-2' },
            pages: { class: 'p-0' },
            page: { class: '!min-w-[30px] !h-[30px] !rounded-[6px] !mx-0.5' },
            first: { class: '!min-w-[30px] !h-[30px] !rounded-[6px] !mx-0.5' },
            prev: { class: '!min-w-[30px] !h-[30px] !rounded-[6px] !mx-0.5' },
            next: { class: '!min-w-[30px] !h-[30px] !rounded-[6px] !mx-0.5' },
            last: { class: '!min-w-[30px] !h-[30px] !rounded-[6px] !mx-0.5' },
            current: { class: 'hidden sm:inline-block text-sm px-2 self-center' }
        }"
    >
        <!-- PrimeVue Paginator Slots can be used here for further customization if needed -->
        <!-- Example:
        <template #start>
            <Button icon="pi pi-refresh" text rounded />
        </template>
        -->
    </Paginator>
</template>

<script lang="ts" setup>
import { computed } from 'vue'
import Paginator from 'primevue/paginator'
import type { PaginationLayout } from './types'

const emit = defineEmits<{
    'update:model-value': [page: number]
    'page-changed': [page: number]
}>()

interface IPaginationProps {
    modelValue?: number
    totalPages?: number
    perPage?: number
    totalItems?: number
    layout?: PaginationLayout
    showIcons?: boolean
    sliceLength?: number
    previousLabel?: string
    nextLabel?: string
    enableFirstAndLastButtons?: boolean
    showLabels?: boolean
    large?: boolean
}

const props = withDefaults(defineProps<IPaginationProps>(), {
    modelValue: 1,
    totalPages: undefined,
    perPage: 10,
    totalItems: 10,
    layout: 'pagination',
    showIcons: false,
    sliceLength: 2,
    previousLabel: 'Prev',
    nextLabel: 'Next',
    enableFirstAndLastButtons: false,
    showLabels: true,
    large: false,
})

// Calculate the 'first' record index for Paginator (0-based)
const first = computed(() => (props.modelValue - 1) * props.perPage)

// Determine the Paginator template based on layout prop
const paginatorTemplate = computed(() => {
    let start = ''
    let end = ''
    if (props.enableFirstAndLastButtons) {
        start = 'FirstPageLink PrevPageLink'
        end = 'NextPageLink LastPageLink'
    } else {
        start = 'PrevPageLink'
        end = 'NextPageLink'
    }

    if (props.layout === 'table') {
        return 'CurrentPageReport RowsPerPageDropdown' // Common table layout
    } else if (props.layout === 'navigation') {
        return `${start} ${end}`
    } else { // Default 'pagination' layout
        return `${start} PageLinks ${end} CurrentPageReport`
    }
})

// Handle the page change event from Paginator
const onPageChange = (event: { page: number; first: number; rows: number; pageCount: number }) => {
    const newPage = event.page + 1 // event.page is 0-based
    emit('update:model-value', newPage)
    emit('page-changed', newPage)
    // You might want to handle rows change if RowsPerPageDropdown is used
    // if (event.rows !== props.perPage) { emit('update:perPage', event.rows) }
}
</script>