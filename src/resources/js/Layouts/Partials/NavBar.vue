<script setup>
import {Link, router, usePage} from "@inertiajs/vue3";
import {Dropdown} from "flowbite";
import {computed, ref} from "vue";
import {useViewStore} from "@/store/view.js";
import ThemeSwitcher from "@/Components/ThemeSwitcher.vue";

const viewStore = useViewStore();

let dropdown = null;

const hideDropdown = () => {
    if (!dropdown) {
        dropdown = new Dropdown(
            document.getElementById("dropdown-user"),
            document.getElementById("dropdown-user-button")
        );
    }
    dropdown.hide()
};

const isDarkMode = ref(localStorage.getItem('color-theme') === 'dark');

const wallet = ref(usePage().props.data.wallet);

const emit = defineEmits(['toggleSidebar']);
const toggleSidebar = () => {
    emit('toggleSidebar');
}

const formatNumber = (num) => {
    const roundedNum = Math.round(num * 100) / 100;
    return roundedNum.toLocaleString('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    });
}

const walletFormated = computed(() => {
    return {
        merchant_balance: formatNumber(wallet.value.merchant_balance),
        trust_balance: formatNumber(wallet.value.trust_balance),
        reserve_balance: formatNumber(wallet.value.reserve_balance),
    }
});

router.on('success', (event) => {
    wallet.value = usePage().props.data.wallet;
})
</script>

<template>
    <nav class="z-50 w-full">
        <div>
            <div class="flex items-center justify-between">
                <!-- Левая часть: бургер + лого -->
                <div class="flex items-center justify-start rtl:justify-end">
                    <button
                        type="button"
                        class="inline-flex items-center p-2 text-muted-foreground rounded-xl lg:hidden hover:bg-muted focus:outline-none focus:ring-2 focus:ring-ring"
                        @click.prevent="toggleSidebar"
                    >
                        <span class="sr-only">Открыть меню</span>
                        <svg class="sm:w-8 sm:h-8 w-7 h-7" aria-hidden="true" fill="currentColor" viewBox="0 0 20 20">
                            <path clip-rule="evenodd" fill-rule="evenodd" d="M2 4.75A.75.75 0 012.75 4h14.5a.75.75 0 010 1.5H2.75A.75.75 0 012 4.75zm0 10.5a.75.75 0 01.75-.75h7.5a.75.75 0 010 1.5h-7.5a.75.75 0 01-.75-.75zM2 10a.75.75 0 01.75-.75h14.5a.75.75 0 010 1.5H2.75A.75.75 0 012 10z"/>
                        </svg>
                    </button>
                    <Link :href="route('dashboard')" class="flex ms-2 md:me-24">
                        <img
                            :src="isDarkMode ? '/images/light.png' : '/images/dark.png'"
                            loading="lazy"
                            alt="Логотип"
                            class="w-full max-w-[200px]"
                        />
                    </Link>
                </div>

                <!-- Правая часть: баланс + профиль -->
                <div class="flex items-center space-x-3">
                    <!-- Баланс мерчанта -->
                    <div v-show="viewStore.isMerchantViewMode" class="lg:flex items-center hidden text-nowrap gap-1.5">
                        <svg class="w-5 h-5 text-primary" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8H5m12 0a1 1 0 0 1 1 1v2.6M17 8l-4-4M5 8a1 1 0 0 0-1 1v10a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-2.6M5 8l4-4 4 4m6 4h-4a2 2 0 1 0 0 4h4a1 1 0 0 0 1-1v-2a1 1 0 0 0-1-1Z"/>
                        </svg>
                        <span class="text-base font-semibold text-foreground">{{ walletFormated.merchant_balance }}</span>
                        <span class="text-sm text-muted-foreground">USDT</span>
                    </div>

                    <!-- Баланс трейдера -->
                    <div v-show="viewStore.isTraderViewMode" class="lg:flex items-center hidden text-nowrap gap-1.5">
                        <svg class="w-5 h-5 text-primary" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8H5m12 0a1 1 0 0 1 1 1v2.6M17 8l-4-4M5 8a1 1 0 0 0-1 1v10a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-2.6M5 8l4-4 4 4m6 4h-4a2 2 0 1 0 0 4h4a1 1 0 0 0 1-1v-2a1 1 0 0 0-1-1Z"/>
                        </svg>
                        <span class="text-base font-semibold text-foreground">{{ walletFormated.trust_balance }}</span>
                        <span class="text-sm text-muted-foreground">USDT</span>
                        <span class="inline-flex items-center bg-muted text-muted-foreground text-xs font-medium px-2.5 py-1 rounded-full gap-1 ml-1">
                            <svg class="w-3.5 h-3.5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14v3m-3-6V7a3 3 0 1 1 6 0v4m-8 0h10a1 1 0 0 1 1 1v7a1 1 0 0 1-1 1H7a1 1 0 0 1-1-1v-7a1 1 0 0 1 1-1Z"/>
                            </svg>
                            {{ wallet.reserve_balance }} USDT
                        </span>
                    </div>

                    <!-- Профиль -->
                    <div class="flex items-center">
                        <div
                            id="dropdown-user-button"
                            data-dropdown-toggle="dropdown-user"
                            class="flex items-center gap-3 cursor-pointer hover:bg-muted py-2 px-3 rounded-xl transition-colors duration-150"
                        >
                            <!-- Аватар-заглушка -->
                            <div class="w-8 h-8 rounded-full bg-primary/15 flex items-center justify-center flex-shrink-0">
                                <i class="pi pi-user text-primary text-sm"></i>
                            </div>
                            <div class="sm:block hidden">
                                <p class="text-sm font-medium text-foreground leading-tight">
                                    {{ $page.props.auth.user.email }}
                                </p>
                                <p class="text-xs text-muted-foreground leading-tight mt-0.5">
                                    {{ $page.props.auth.user.name }}
                                </p>
                            </div>
                            <svg class="w-4 h-4 text-muted-foreground sm:block hidden" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m19 9-7 7-7-7"/>
                            </svg>
                        </div>

                        <!-- Дропдаун профиля -->
                        <div
                            class="z-50 hidden my-4 text-base list-none bg-popover border border-border rounded-xl shadow-lg"
                            id="dropdown-user"
                        >
                            <!-- Мобильный блок с данными -->
                            <div class="px-4 py-3 lg:hidden block border-b border-border">
                                <p class="text-sm font-medium text-foreground">{{ $page.props.auth.user.name }}</p>
                                <p class="text-xs text-muted-foreground truncate mt-0.5">{{ $page.props.auth.user.email }}</p>
                                <div class="mt-2 space-y-1.5">
                                    <div v-show="viewStore.isMerchantViewMode" class="flex items-center gap-2">
                                        <svg class="w-4 h-4 text-primary" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8H5m12 0a1 1 0 0 1 1 1v2.6M17 8l-4-4M5 8a1 1 0 0 0-1 1v10a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-2.6M5 8l4-4 4 4m6 4h-4a2 2 0 1 0 0 4h4a1 1 0 0 0 1-1v-2a1 1 0 0 0-1-1Z"/>
                                        </svg>
                                        <span class="text-sm font-semibold text-foreground">{{ walletFormated.merchant_balance }}</span>
                                        <span class="text-xs text-primary">USDT</span>
                                    </div>
                                    <div v-show="viewStore.isTraderViewMode" class="flex items-center gap-2">
                                        <svg class="w-4 h-4 text-primary" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8H5m12 0a1 1 0 0 1 1 1v2.6M17 8l-4-4M5 8a1 1 0 0 0-1 1v10a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-2.6M5 8l4-4 4 4m6 4h-4a2 2 0 1 0 0 4h4a1 1 0 0 0 1-1v-2a1 1 0 0 0-1-1Z"/>
                                        </svg>
                                        <span class="text-sm font-semibold text-foreground">{{ walletFormated.trust_balance }}</span>
                                        <span class="text-xs text-primary">USDT</span>
                                        <span class="inline-flex bg-muted text-muted-foreground text-xs px-2 py-0.5 rounded-full">
                                            {{ wallet.reserve_balance }} USDT
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <ul role="none" class="w-full py-1 rounded-xl overflow-hidden min-w-[180px]">
                                <li>
                                    <Link
                                        @click="hideDropdown"
                                        :href="route('profile.edit')"
                                        class="flex items-center gap-2 px-4 py-2.5 text-sm text-foreground hover:bg-muted transition-colors"
                                    >
                                        <i class="pi pi-user text-muted-foreground text-xs"></i>
                                        Профиль
                                    </Link>
                                </li>
                                <li>
                                    <Link
                                        @click="hideDropdown"
                                        :href="route('logout')"
                                        method="post"
                                        class="flex items-center gap-2 text-left w-full px-4 py-2.5 text-sm text-destructive hover:bg-destructive/10 transition-colors"
                                    >
                                        <i class="pi pi-sign-out text-xs"></i>
                                        Выход
                                    </Link>
                                </li>
                                <li class="border-t border-border">
                                    <div class="flex items-center p-3 px-4">
                                        <ThemeSwitcher />
                                    </div>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </nav>
</template>

<style scoped>
</style>
