<template>
    <div>
        <template v-if="loading">
            <spinner></spinner>
        </template>
        <template v-else>
            <p><router-link to="/cart">← Cart</router-link></p>
            <h1>Identity</h1>

            <h2>Use your Craft ID</h2>
            <p><router-link to="/login">Login</router-link> or <router-link to="/register">register</router-link> to purchase licenses with your Craft ID.</p>
        </template>
    </div>
</template>

<script>
    import {mapState, mapActions} from 'vuex'

    export default {
        data() {
            return {
                loading: false,
                identityMode: 'craftid',
            }
        },

        computed: {
            ...mapState({
                cart: state => state.cart.cart,
                user: state => state.account.user,
            }),
        },

        methods: {
            ...mapActions({
                getCart: 'cart/getCart',
            }),
        },

        mounted() {
            if (this.user) {
                this.$router.push({path: '/payment'})
                return
            }
            
            this.loading = true

            this.getCart()
                .then(() => {
                    this.loading = false
                })
                .catch(() => {
                    this.loading = false
                })
        }
    }
</script>