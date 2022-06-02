<template>
    <div>
        <div class="flex">
            <div class="flex-1">
                <h2>Billing Address</h2>

                <template v-if="loading">
                    <spinner></spinner>
                </template>

                <template v-else>
                    <template v-if="!showForm && billingAddress">
                        <ul v-if="billingAddress.firstName || billingAddress.lastName || billingAddress.address1 || billingAddress.address2 || billingAddress.city || billingAddress.country || billingAddress.businessName || billingAddress.state || billingAddress.zipCode">
                            <li v-if="billingAddress.firstName || billingAddress.lastName">
                                {{ billingAddress.firstName }}
                                {{ billingAddress.lastName }}
                            </li>
                            <li v-if="billingAddress.businessName">
                                {{ billingAddress.businessName }}
                            </li>
                            <li v-if="billingAddress.address1">
                                {{ billingAddress.address1 }}
                            </li>
                            <li v-if="billingAddress.address2">
                                {{ billingAddress.address2 }}
                            </li>
                            <li v-if="billingAddress.zipCode || billingAddress.city">
                                <template v-if="billingAddress.zipCode">
                                    {{ billingAddress.zipCode }}
                                </template>
                                <template v-if="billingAddress.city">
                                    {{ billingAddress.city }}
                                </template>
                            </li>
                            <li v-if="billingAddress.countryText">
                                {{ billingAddress.countryText }}
                            </li>
                            <li v-if="billingAddress.state">
                                {{ billingAddress.state }}
                            </li>
                        </ul>

                        <p v-else class="text-light">Billing address not
                            defined.</p>
                    </template>
                </template>
            </div>

            <div v-if="!showForm">
                <btn small @click="edit()">
                    <icon icon="pencil" size="sm"/>
                    Edit
                </btn>
            </div>
        </div>

        <template v-if="!loading">
            <form v-if="showForm" @submit.prevent="save()">
                <field label-for="firstName" label="First Name">
                    <textbox id="firstName"
                             v-model="invoiceDetailsDraft.firstName"
                             :errors="errors.firstName"/>
                </field>
                <field label-for="lastName" label="Last Name">
                    <textbox id="lastName"
                             v-model="invoiceDetailsDraft.lastName"
                             :errors="errors.lastName"/>
                </field>
                <field label-for="businessName" label="Business Name">
                    <textbox id="businessName"
                             v-model="invoiceDetailsDraft.businessName"
                             :errors="errors.businessName"/>
                </field>
                <field label-for="address1" label="Address Line 1">
                    <textbox id="address1"
                             v-model="invoiceDetailsDraft.address1"
                             :errors="errors.address1"/>
                </field>
                <field label-for="address2" label="Address Line 2">
                    <textbox id="address2"
                             v-model="invoiceDetailsDraft.address2"
                             :errors="errors.address2"/>
                </field>
                <field label-for="city" label="City">
                    <textbox id="city" v-model="invoiceDetailsDraft.city"
                             :errors="errors.city"/>
                </field>
                <field label-for="country" label="Country">
                    <dropdown id="country" v-model="invoiceDetailsDraft.country"
                              :options="countryOptions"
                              @input="onCountryChange"/>
                </field>
                <field label-for="state" label="State">
                    <dropdown id="state" v-model="invoiceDetailsDraft.state"
                              :options="stateOptions(invoiceDetailsDraft.country)"/>
                </field>
                <field label-for="zipCode" label="Zip Code">
                    <textbox id="zipCode" v-model="invoiceDetailsDraft.zipCode"
                             :errors="errors.zipCode"/>
                </field>

                <div class="flex border-t mt-6 pt-6">
                    <btn class="mr-2" kind="primary" type="submit"
                         :loading="saveLoading" :disabled="saveLoading">Save
                    </btn>
                    <btn @click="cancel()" :disabled="saveLoading">Cancel</btn>
                </div>
            </form>
        </template>
    </div>
</template>

<script>
import {mapState, mapGetters, mapActions} from 'vuex'

export default {
    data() {
        return {
            loading: false,
            saveLoading: false,
            errors: {},
            showForm: false,
            invoiceDetailsDraft: {},
        }
    },

    computed: {
        ...mapState({
            user: state => state.account.user,
            billingAddress: state => state.account.billingAddress,
            countries: state => state.craftId.countries,
        }),

        ...mapGetters({
            countryOptions: 'craftId/countryOptions',
            stateOptions: 'craftId/stateOptions',
        }),
    },

    methods: {
        ...mapActions({
            getCountries: 'craftId/getCountries',
        }),

        edit() {
            this.showForm = true;

            if (this.billingAddress) {
                this.invoiceDetailsDraft = JSON.parse(JSON.stringify(this.billingAddress));
            }
        },

        save() {
            this.saveLoading = true

            let data = {
                firstName: this.invoiceDetailsDraft.firstName,
                lastName: this.invoiceDetailsDraft.lastName,
                businessName: this.invoiceDetailsDraft.businessName,
                address1: this.invoiceDetailsDraft.address1,
                address2: this.invoiceDetailsDraft.address2,
                city: this.invoiceDetailsDraft.city,
                state: this.invoiceDetailsDraft.state,
                zipCode: this.invoiceDetailsDraft.zipCode,
                country: this.invoiceDetailsDraft.country,
            }

            if (this.billingAddress) {
                data = Object.assign({}, data, {
                    id: this.billingAddress.id,
                    businessTaxId: this.billingAddress.businessTaxId,
                });
            }

            this.$store.dispatch('account/saveBillingInfo', data)
                .then((response) => {
                    this.saveLoading = false

                    if (response.data && response.data.error) {
                        const errorMessage = response.data.error
                        this.$store.dispatch('app/displayError', errorMessage);
                        return null;
                    }

                    this.$store.dispatch('app/displayNotice', 'Billing address saved.');
                    this.showForm = false;
                    this.errors = {};
                })
                .catch((response) => {
                    this.saveLoading = false
                    const errorMessage = response.data && response.data.error ? response.data.error : 'Couldn’t save billing address.';
                    this.$store.dispatch('app/displayError', errorMessage);
                    this.errors = response.data && response.data.errors ? response.data.errors : {};
                });
        },

        cancel() {
            this.showForm = false;
            this.errors = {};
        },

        onCountryChange() {
            this.invoiceDetailsDraft.state = null
            const stateOptions = this.stateOptions(this.invoiceDetailsDraft.country);

            if (stateOptions.length) {
                this.invoiceDetailsDraft.state = stateOptions[0].value
            }
        }
    },

    mounted() {
        this.loading = true

        this.getCountries()
            .then(() => {
                this.loading = false
            })
            .catch(() => {
                this.loading = false
                this.$store.dispatch('app/displayNotice', 'Couldn’t get countries.');
            })
    }
}
</script>
