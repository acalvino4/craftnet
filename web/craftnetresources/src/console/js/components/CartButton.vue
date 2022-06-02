<template>
    <router-link
        :class="{
            'cart-button relative block header-toggle px-3 py-2 rounded-md': true,
            'hover:bg-white dark:hover:bg-black hover:bg-opacity-30': context !== 'app',
            'hover:bg-black dark:hover:bg-white hover:bg-opacity-5': context === 'app'
        }"
        to="/cart">
        <icon set="solid" icon="shopping-cart" class="w-5 h-5"
              :class="{
                'text-blue-600 dark:text-blue-400': context !== 'app',
                'text-gray-500': context === 'app'
              }"/>

        <div
            class="cart-badge rounded-full leading-3 absolute text-white text-center top-0 right-0 px-1.5 py-0.5 text-xs -mt-0.5"
            :class="{
                invisible: !cartTotalItems,
                'bg-blue-600 dark:bg-blue-400': context !== 'app',
                'bg-gray-500': context === 'app'
            }">
            {{ cartTotalItems }}
        </div>
    </router-link>
</template>

<script>
import {mapGetters} from 'vuex';

export default {
    props: {
        context: String,
    },
    computed: {
        ...mapGetters({
            cartTotalItems: 'cart/cartTotalItems',
        }),
    },

}
</script>

<style lang="scss">
.cart-button {
    .cart-badge {
        font-size: 0.625rem;
    }
}
</style>