<template>
    <div class="site flex flex-col fixed inset-0">
        <template v-if="$route.meta.centerContent">
            <div class="polkadots">
                <div class="polkadot opacity-50 dark:opacity-20 absolute transform -translate-x-1/2 -translate-y-1/3 top-0 left-0 bg-purple-300 dark:bg-purple-900 rounded-full"></div>
                <div class="polkadot opacity-50 dark:opacity-20 absolute top-0 right-0  transform translate-x-1/2 -translate-y-1/3 bg-blue-300 dark:bg-blue-900 rounded-full"></div>
                <div class="polkadot opacity-50 dark:opacity-20 absolute bottom-0 left-1/2 transform -translate-x-1/2 translate-y-1/2 bg-red-300 dark:bg-red-900 rounded-full"></div>
            </div>
        </template>

        <div
            class="fixed w-full z-30 flex px-4 md:px-8 py-4 justify-between items-center dark:bg-black backdrop-filter backdrop-blur bg-opacity-25"
            :class="{
                'bg-white': $route.meta.centerContent,
                'bg-gray-200': !$route.meta.centerContent,
            }"
        >
            <div>
                <router-link class="flex rounded p-0.5 items-center -mx-0.5"
                             to="/">
                    <img class="max-w-none h-8" src="~@/common/images/craftcms.svg"/>
                </router-link>
            </div>

            <div class="flex items-center space-x-2">
                <cart-button></cart-button>

                <router-link
                    class="px-4 py-2 hover:bg-white dark:hover:bg-black hover:bg-opacity-30 rounded-md hover:no-underline"
                    to="/">Sign in
                </router-link>
            </div>
        </div>

        <div class="flex-1 flex pt-18 overflow-auto">
            <template v-if="$route.meta.centerContent">
                <div class="relative z-20 flex-1 flex  p-4 md:p-8">
                    <div class="w-full max-w-lg m-auto">
                        <router-view :key="$route.path"></router-view>
                    </div>
                </div>
            </template>

            <template v-else>
                <div
                    class="relative z-20 bg-white dark:bg-gray-900 flex-1  p-4 md:p-8">
                    <div class="w-full max-w-screen-lg m-auto">
                        <router-view :key="$route.path"></router-view>
                    </div>
                </div>
            </template>
        </div>
    </div>
</template>

<script>
import CartButton from '@/console/js/components/CartButton';

export default {
    components: {
        CartButton,
    },
}
</script>

<style>
.polkadots {
    @apply absolute z-10 left-1/2 top-1/2 transform -translate-x-1/2 -translate-y-1/2;
    width: 100vw;
    height: 100vw;
}

.polkadot {
    @apply mix-blend-multiply filter blur-3xl;

    width: 130%;
    height: 130%;
}
</style>