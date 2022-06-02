<template>
    <modal-headless :isOpen="showModal" @close="$emit('close')">
        <div>
            <h2 class="mb-3">Renew Plugin License</h2>

            <div>
                <spinner v-if="loading"></spinner>

                <template v-else>
                    <dropdown v-model="renew" :options="extendUpdateOptions"/>

                    <table class="mt-6 table">
                        <thead>
                        <tr>
                            <th>Item</th>
                            <th>Renewal Date</th>
                            <th>New Renewal Date</th>
                            <th></th>
                        </tr>
                        </thead>
                        <tbody>
                        <tr>
                            <td>
                                {{ license.plugin.name }}
                            </td>
                            <td>{{
                                    $filters.parseDate(license.expiresOn.date).toFormat('yyyy-MM-dd')
                                }}
                            </td>
                            <td>{{ expiryDate }}</td>
                            <td class="text-right">{{ $filters.currency(price) }}</td>
                        </tr>
                        <tr>
                            <th colspan="3" class="text-right">Total</th>
                            <td class="text-right"><strong>{{ $filters.currency(price) }}</strong></td>
                        </tr>
                        </tbody>
                    </table>


                    <div class="mt-6 flex justify-between items-center">

                    </div>
                </template>
            </div>
        </div>

        <template v-slot:footer>
            <spinner v-if="addToCartLoading"></spinner>

            <btn @click="$emit('close')">Cancel</btn>
            <btn ref="submitBtn" kind="primary" @click="addToCart()"
                 :disabled="addToCartLoading">Add to cart
            </btn>
        </template>
    </modal-headless>
</template>

<script>
import {mapGetters, mapActions} from 'vuex'

import ModalHeadless from "../../ModalHeadless";
export default {
    components: {ModalHeadless},
    props: {
        license: {
            type: Object,
            required: true,
        },
        showModal: {
            type: Boolean,
            required: true,
            default: false,
        },
    },


    data() {
        return {
            loading: false,
            addToCartLoading: false,
            renew: 0,
        }
    },

    computed: {
        ...mapGetters({
            cartItems: 'cart/cartItems',
        }),

        renewalOptions() {
            return this.license.renewalOptions
        },

        extendUpdateOptions() {
            if (!this.renewalOptions) {
                return []
            }

            let options = [];

            for (let i = 0; i < this.renewalOptions.length; i++) {
                const renewalOption = this.renewalOptions[i]
                const date = renewalOption.expiryDate
                const formattedDate = this.$filters.parseDate(date).toFormat('yyyy-MM-dd')
                let label = "Extend updates until " + formattedDate

                const baseAmount = this.renewalOptions[this.renew].amount
                const amountDiff = renewalOption.amount - baseAmount

                if (amountDiff !== 0) {
                    let prefix = ''

                    if (amountDiff > 0) {
                        prefix = '+'
                    }
                    label += ' (' + prefix + this.$filters.currency(amountDiff) + ')'
                }

                options.push({
                    label: label,
                    value: i,
                })
            }

            return options;
        },

        price() {
            return (parseFloat(this.renew) + 1) * this.license.edition.renewalPrice;
        },

        expiryDate() {
            if (!this.renewalOptions) {
                return null
            }

            if (!this.renewalOptions[this.renew]) {
                return null
            }

            const date = this.renewalOptions[this.renew].expiryDate

            return this.$filters.parseDate(date).toFormat('yyyy-MM-dd')
        },
    },

    methods: {
        ...mapActions({
            getCoreData: 'pluginStore/getCoreData',
        }),

        addToCart() {
            const expiryDate = this.renewalOptions[this.renew].expiryDate
            const item = {
                type: 'plugin-renewal',
                licenseKey: this.license.key,
                expiryDate: expiryDate,
            }

            this.addToCartLoading = true

            this.$store.dispatch('cart/addToCart', [item])
                .then(() => {
                    this.addToCartLoading = false
                    this.$router.push({path: '/cart'})
                    this.$emit('addToCart')
                })
                .catch(errorMessage => {
                    this.addToCartLoading = false
                    this.$store.dispatch('app/displayError', errorMessage);
                })
        },
    },

    watch: {
        showModal(value) {
            if (value === true) {
                this.$nextTick(() => {
                    this.loading = true

                    this.getCoreData()
                        .then(() => {
                            this.loading = false
                            this.renew = 0
                            this.$nextTick(() => {
                                this.$refs.submitBtn.$el.focus()
                            })
                        })
                        .catch(() => {
                            this.loading = false
                        })
                })
            }
        }
    }
};
</script>