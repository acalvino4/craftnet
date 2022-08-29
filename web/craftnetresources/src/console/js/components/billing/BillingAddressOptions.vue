<template>
  <div class="space-y-4">
    <template v-for="address in addresses">
      <div class="flex border-b py-4">
        <div class="mt-1 mr-2">
          <input
            :id="'address-' + address.id"
            type="radio"
            :value="address.id"
            v-model="localBillingAddressId"
          />
        </div>
        <label class="flex flex-1" :for="'address-' + address.id">
          <div class="flex-1">
            <div class="flex items-center space-x-2">
              <div v-if="address.countryCode">{{address.countryCode}}</div>
              <div v-if="address.addressLine1">{{address.addressLine1}}</div>
              <div v-if="address.locality">{{address.locality}}</div>
              <div v-if="address.postalCode">{{address.postalCode}}</div>
            </div>
            <div class="text-sm font-mono text-gray-500">#{{address.id}}</div>
          </div>
          <div>
            <a href="#">Edit</a>
          </div>
        </label>
      </div>
    </template>
    <div>
      <a href="#">+ Add a new billing address</a>
    </div>
  </div>
</template>

<script>
import {mapState} from 'vuex';

export default {
  props: ['billingAddressId'],

  computed: {
    ...mapState({
      addresses: state => state.addresses.addresses,
    }),

    localBillingAddressId: {
      get() {
        return this.billingAddressId
      },
      set(value) {
        this.$emit('update:billingAddressId', value)
      }
    },

    addressRadioOptions() {
      const options = []

      if (this.addresses) {
        this.addresses.forEach(address => {
          options.push({
            label: '#' + address.id,
            value: address.id,
          })
        })
      }

      return options
    },
  },

  mounted() {
    this.$store.dispatch('addresses/getAddresses')
  }
}
</script>