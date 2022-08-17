<template>
  <div>
    <div>
        <h2>Addresses</h2>
    </div>

    <div class="mt-6 grid grid-cols-3 gap-4">
      <div class="flex">
        <a
          class="border border-gray-300 dark:border-gray-600 border-dashed block flex-1 rounded-md flex items-center justify-center p-4"
          href="#"
          @click="add"
        >
          <div>
            <icon
              icon="plus"
              class="w-4 h-4" />

            Add an address
          </div>
        </a>
      </div>
      <template v-for="(address, addressKey) in addresses" :key="addressKey">
        <address-card
          :address="address"
          @edit="edit(address)"
          @remove="remove(address.id)" />
      </template>
    </div>

    <address-modal
      :isOpen="showAddressModal"
      :edit-address="editAddress"
      @close="showAddressModal = false"
    />
  </div>
</template>

<script>
import {mapState} from 'vuex';
import AddressCard from './addresses/AddressCard';
import AddressModal from './addresses/AddressModal';

export default {
  components: {AddressModal, AddressCard},
  data() {
    return {
      countriesLoading: false,
      countryCode: null,
      address: {},
      showAddressModal: false,
      editAddress: null,
    }
  },

  computed: {
    ...mapState({
      addresses: state => state.addresses.addresses,
    }),
  },

  methods: {
    add() {
      this.editAddress = {
        title: 'Address',
        countryCode: '',
      }
      this.showAddressModal = true
    },
    edit(address) {
      this.showAddressModal = true
      this.editAddress = address
    },
    remove(addressId) {
      if (!confirm("Are you sure you want to remove this address?")) {
        return null;
      }

      this.$store.dispatch('addresses/deleteAddress', addressId)
    },
  },

  mounted() {
    this.$store.dispatch('addresses/getAddresses');
  }
}
</script>