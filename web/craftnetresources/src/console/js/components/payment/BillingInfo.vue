<template>
  <div>
    <field
      :vertical="vertical"
      label-for="first-name"
      label="First Name">
      <textbox
        placeholder="First Name"
        id="first-name"
        v-model="computedBillingInfo.firstName"
        :errors="errors['billingAddress.firstName']" />
    </field>
    <field
      :vertical="vertical"
      label-for="last-name"
      label="Last Name">
      <textbox
        placeholder="Last Name"
        id="last-name"
        v-model="computedBillingInfo.lastName"
        :errors="errors['billingAddress.lastName']" />
    </field>
    <field
      :vertical="vertical"
      label-for="business-name"
      label="Business Name">
      <textbox
        placeholder="Business Name"
        id="business-name"
        v-model="computedBillingInfo.businessName"
        :errors="errors['billingAddress.businessName']" />
    </field>
    <field
      :vertical="vertical"
      label-for="business-tax-id"
      label="Business Tax ID">
      <textbox
        placeholder="Business Tax ID"
        id="business-tax-id"
        v-model="computedBillingInfo.businessTaxId"
        :errors="errors['billingAddress.businessTaxId']" />
    </field>
    <field
      :vertical="vertical"
      label-for="address-1"
      label="Address 1">
      <textbox
        placeholder="Address 1"
        id="address-1"
        v-model="computedBillingInfo.address1"
        :errors="errors['billingAddress.address1']" />
    </field>
    <field
      :vertical="vertical"
      label-for="address-2"
      label="Address 2">
      <textbox
        placeholder="Address 2"
        id="address-2"
        v-model="computedBillingInfo.address2"
        :errors="errors['billingAddress.address2']" />
    </field>

    <field
      :vertical="vertical"
      label-for="city"
      label="City">
      <textbox
        placeholder="City"
        id="city"
        v-model="computedBillingInfo.city"
        :errors="errors['billingAddress.city']" />
    </field>
    <field
      :vertical="vertical"
      label-for="zip-code"
      label="Zip Code">
      <textbox
        placeholder="Zip Code"
        id="zip-code"
        v-model="computedBillingInfo.zipCode"
        :errors="errors['billingAddress.zipCode']" />
    </field>

    <field
      :vertical="vertical"
      label-for="country"
      label="Country">
      <template v-if="loading">
        <spinner></spinner>
      </template>
      <template v-else>
        <dropdown
          :fullwidth="true"
          :options="countryOptions"
          v-model="computedBillingInfo.country"
          id="country"
          :errors="errors['billingAddress.country']"
          @input="onCountryChange" />
      </template>
    </field>
    <field
      :vertical="vertical"
      label-for="state"
      label="State">
      <template v-if="!loading">
        <dropdown
          :fullwidth="true"
          :options="stateOptions(computedBillingInfo.country)"
          v-model="computedBillingInfo.state"
          id="state"
          :errors="errors['billingAddress.state']"
          @input="onStateChange" />
      </template>
    </field>
  </div>
</template>

<script>
import {mapGetters, mapActions} from 'vuex'

export default {
  props: ['billingInfo', 'errors', 'vertical'],

  data() {
    return {
      loading: false,
    }
  },

  computed: {
    ...mapGetters({
      countryOptions: 'craftId/countryOptions',
      stateOptions: 'craftId/stateOptions',
    }),

    computedBillingInfo: {
      get() {
        return this.billingInfo
      },

      set(billingInfo) {
        this.$emit('update:billingInfo', billingInfo)
      }
    }
  },

  methods: {
    ...mapActions({
      getCountries: 'craftId/getCountries',
    }),

    onCountryChange() {
      const billingInfo = JSON.parse(JSON.stringify(this.billingInfo))

      billingInfo.state = null
      const stateOptions = this.stateOptions(billingInfo.country)

      if (stateOptions.length) {
        billingInfo.state = stateOptions[0].value
      }

      this.$emit('update:billingInfo', billingInfo)
    },

    onStateChange(value) {
      const billingInfo = JSON.parse(JSON.stringify(this.billingInfo))
      billingInfo.state = value

      this.$emit('update:billingInfo', billingInfo)
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
