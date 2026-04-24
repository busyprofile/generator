import {defineStore} from 'pinia'
import { toRaw } from 'vue'

export const useTableFiltersStore = defineStore('tableFilters', {
    state: () => {
        return {
            page: 1,
            per_page: 9,
            total: 9,
            tab: "",
            filters: {},
            filtersVariants: {},
        };
    },
    getters: {
        getCurrentPage: (state) => state.page,
        getPerPage: (state) => state.per_page ?? 9,
        getTotal: (state) => state.total,
        getTab: (state) => state.tab,
        getFilters: (state) => state.filters,
        getFiltersVariants: (state) => state.filtersVariants,
        getQueryData: (state) => {
            return toRaw({
                page: state.page,
                per_page: state.per_page,
                tab: state.tab,
                filters: toRaw(state.filters)
            })
        }
    },
    actions: {
        setCurrentPage(current_page) {
            this.page = current_page ?? 1;
        },
        setPerPage(per_page) {
            const validOptions = [6, 9, 15, 21, 27, 51, 99];
            this.per_page = validOptions.includes(per_page) ? per_page : 9;
        },
        setTotal(total) {
            this.total = total;
        },
        setTab(tab) {
            this.tab = tab;
        },
        setMeta(meta = null) {
            this.page = meta?.current_page ?? 1;
            this.setPerPage(meta?.per_page);
            this.total = meta?.total ?? 9;
        },
        setFilters(filters) {
            this.filters = filters;
        },
        setFiltersVariants(filtersVariants = null) {
            this.filtersVariants = filtersVariants ?? {};
        }
    }
})
