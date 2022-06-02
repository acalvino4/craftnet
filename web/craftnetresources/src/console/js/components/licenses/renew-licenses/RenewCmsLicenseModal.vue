<template>
    <modal-headless :isOpen="showModal" @close="$emit('close')">
        <div>
            <div>
                <h2 class="mb-3">Renew Licenses</h2>

                <div>
                    <dropdown v-model="renew" @input="onRenewChange"
                              :options="renewOptions"/>

                    <table class="table mt-6">
                        <thead>
                        <tr>
                            <td><input type="checkbox" v-model="checkAllChecked"
                                       ref="checkAll" @change="checkAll"></td>
                            <th>Item</th>
                            <th>Renewal Date</th>
                            <th>New Renewal Date</th>
                            <th></th>
                        </tr>
                        </thead>
                        <tbody>
                        <template v-for="renewableLicense in renewableLicenses"
                                  :key="getRenewableLicenseKey(renewableLicense)">
                            <renewable-license-table-row
                                :renewableLicense="renewableLicense"
                                :itemKey="getRenewableLicenseKey(renewableLicense)"
                                :isChecked="checkedLicenses[getRenewableLicenseKey(renewableLicense)]"
                                @checkLicense="checkLicense($event)"
                            ></renewable-license-table-row>
                        </template>
                        <tr>
                            <th colspan="4" class="text-right">Total</th>
                            <td><strong>{{ $filters.currency(total) }}</strong></td>
                        </tr>
                        </tbody>
                    </table>

                    <spinner v-if="loading"></spinner>
                </div>
            </div>
        </div>

        <template v-slot:footer>
            <btn @click="$emit('close')">Cancel</btn>
            <btn ref="submitBtn" @click="addToCart()" kind="primary"
                 :disabled="!hasCheckedLicenses">Add to cart
            </btn>
        </template>
    </modal-headless>
</template>

<script>
import helpers from '@/console/js/mixins/helpers.js'
import RenewableLicenseTableRow from '@/console/js/components/licenses/renew-licenses/RenewableLicenseTableRow'

import {mapGetters, mapState} from 'vuex'
import ModalHeadless from '@/console/js/components/ModalHeadless';

export default {
    mixins: [helpers],

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

    components: {
        ModalHeadless,
        RenewableLicenseTableRow,
    },

    data() {
        return {
            loading: false,
            checkAllChecked: false,
            renew: 0,
            checkedLicenses: {},
        }
    },

    computed: {
        ...mapState({
            renewLicense: state => state.app.renewLicense,
            user: state => state.account.user,
        }),
        ...mapGetters({
            cartItems: 'cart/cartItems',
        }),

        renewOptions() {
            const renewalOptions = this.license.renewalOptions

            if (!renewalOptions) {
                return []
            }

            const pluginRenewalOptions = this.license.pluginRenewalOptions
            let options = [];

            for (let i = 0; i < renewalOptions.length; i++) {
                const renewalOption = renewalOptions[i]
                const date = renewalOption.expiryDate
                const formattedDate = this.$filters.parseDate(date).toFormat('yyyy-MM-dd')
                let label = "Extend updates until " + formattedDate

                // cms amount
                let currentAmount = renewalOption.amount

                if (!this.license.expirable) {
                    currentAmount = 0
                }

                const renewableLicenses = this.getRenewableLicenses(this.license, i, this.cartItems)


                // plugin amounts
                renewableLicenses.forEach((renewableLicense) => {
                    // only keep checked licenses
                    if (this.checkedLicenses[this.getRenewableLicenseKey(renewableLicense)]) {
                        // extract plugin handle from the plugin licenses

                        if (renewableLicense.type === 'plugin-renewal') {
                            this.license.pluginLicenses.forEach(pluginLicense => {
                                if (!pluginLicense.expiresOn) {
                                    // Stop there if the plugin doesn’t expire
                                    return
                                }

                                if (pluginLicense.key === renewableLicense.key) {
                                    const pluginRenewalOptionKey = pluginLicense.key

                                    // find plugin renewal options matching this plugin handle
                                    const option = pluginRenewalOptions[pluginRenewalOptionKey][i]

                                    // add plugin option amount
                                    currentAmount += option.amount
                                }
                            })
                        }
                    }
                })

                // amount difference
                const amountDiff = currentAmount - this.total

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

            return options
        },

        renewableLicenses() {
            return this.getRenewableLicenses(this.license, this.renew, this.cartItems)
        },

        hasCheckedLicenses() {
            return this.hasCheckValue(1)
        },

        total() {
            let total = 0

            this.renewableLicenses.forEach(function(renewableLicense) {
                if (!this.checkedLicenses[this.getRenewableLicenseKey(renewableLicense)]) {
                    return
                }

                total += renewableLicense.amount
            }.bind(this))

            return total
        }
    },

    methods: {
        hasCheckValue(checkValue) {
            let found = false

            for (const property in this.checkedLicenses) {
                const checked = this.checkedLicenses[property]

                if (checked === checkValue) {
                    found = true
                }
            }

            return found
        },

        onRenewChange($event) {
            this.renew = $event.target.value

            let checkedLicenses = {}

            this.renewableLicenses.forEach(function(renewableLicense) {
                let renewableLicenseKey = this.getRenewableLicenseKey(renewableLicense)
                let value = 0

                if (this.checkedLicenses[renewableLicenseKey] === 1) {
                    value = 1
                }

                checkedLicenses[renewableLicenseKey] = value
            }.bind(this))

            this.checkedLicenses = checkedLicenses
            this.checkAllChecked = true

            this.$nextTick(() => {
                if (this.hasCheckValue(0)) {
                    this.checkAllChecked = false
                }
            })
        },

        checkLicense({$event, key}) {
            this.checkedLicenses[key] = $event.target.checked ? 1 : 0

            if (!this.hasCheckValue(0)) {
                this.checkAllChecked = true
            } else {
                this.checkAllChecked = false
            }
        },

        checkAll($event) {
            let checkedLicenses = {}
            this.renewableLicenses.forEach(function(renewableLicense) {
                let value

                if (renewableLicense.type === 'cms-renewal') {
                    value = 1
                } else {
                    value = renewableLicense.key && $event.target.checked ? 1 : 0
                }

                checkedLicenses[this.getRenewableLicenseKey(renewableLicense)] = value
            }.bind(this))

            this.checkedLicenses = checkedLicenses
        },

        addToCart() {
            const renewableLicenses = this.renewableLicenses
            const items = []

            renewableLicenses.forEach(function(renewableLicense) {
                if (!this.checkedLicenses[this.getRenewableLicenseKey(renewableLicense)]) {
                    return
                }

                if (!renewableLicense.key) {
                    return
                }

                const type = renewableLicense.type
                const licenseKey = renewableLicense.key
                const expiryDate = renewableLicense.expiryDate

                const item = {
                    type,
                    licenseKey,
                    expiryDate,
                }

                items.push(item)
            }.bind(this))

            this.loading = true

            this.$store.dispatch('cart/addToCart', items)
                .then(() => {
                    this.loading = false
                    this.$router.push({path: '/cart'})
                    this.$emit('addToCart')
                })
                .catch((errorMessage) => {
                    this.loading = false
                    this.$store.dispatch('app/displayError', errorMessage)
                })
        },

        getRenewableLicenseKey(renewableLicense) {
            return renewableLicense.type + '-' + renewableLicense.key
        }
    },

    watch: {
        showModal(value) {
            if (value === true && !this.checkAllChecked) {
                this.$nextTick(() => {
                    this.$refs.checkAll.click()
                    this.$refs.submitBtn.$el.focus()
                })
            }
        }
    },
}
</script>

<style lang="scss">
#renew-licenses-modal {
    .modal {
        .modal-dialog {
            @apply relative;
            min-width: 800px;
            min-height: 600px;

            .modal-content {
                @apply absolute inset-0;
            }
        }
    }
}
</style>
