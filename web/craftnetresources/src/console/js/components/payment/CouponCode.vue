<template>
    <div>
        <textbox placeholder="XXXXXXX" id="coupon-code" size="12"
                 v-model="couponCode" @input="couponCodeChange"
                 :errors="couponCodeError"/>
        <spinner v-if="couponCodeLoading"></spinner>
    </div>
</template>

<script>
import {mapState} from 'vuex'

export default {
    data() {
        return {
            couponCode: null,
            couponCodeLoading: false,
            couponCodeSuccess: false,
            couponCodeError: false,
            couponCodeTimeout: false,
        }
    },

    computed: {
        ...mapState({
            cart: state => state.cart.cart,
        }),
    },

    methods: {
        couponCodeChange() {
            clearTimeout(this.couponCodeTimeout)
            this.couponCodeSuccess = false
            this.couponCodeError = null

            this.couponCodeTimeout = setTimeout(function() {
                this.couponCodeLoading = true

                const data = {
                    couponCode: (this.couponCode ? this.couponCode : null),
                }

                this.$store.dispatch('cart/saveCart', data)
                    .then(() => {
                        this.couponCodeSuccess = true
                        this.couponCodeError = null
                        this.staticCartTotal = this.cart.totalPrice
                        this.couponCodeLoading = false
                    })
                    .catch(response => {
                        if (response.response.data.errors[0].message) {
                            this.couponCodeError = [response.response.data.errors[0].message]
                        } else {
                            this.couponCodeError = true
                        }

                        this.staticCartTotal = this.cart.totalPrice
                        this.couponCodeLoading = false
                    })
            }.bind(this), 500)
        }
    },

    mounted() {
        this.couponCode = this.cart.couponCode
    }
}
</script>
