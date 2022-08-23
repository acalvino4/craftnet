<template>
  <modal-headless
    :isOpen="isOpen"
    @close="close"
  >
    <form method="post" action="#" @submit.prevent="save">
      <h3>
        <template v-if="address.id">
          Edit Address #{{address.id}}
        </template>
        <template v-else>
          Add new address
        </template>
      </h3>

      <address-fields
        v-model:address="address"
      />

      <input class="hidden" type="submit" value="Save" />
    </form>
    <template v-slot:footer>
      <btn @click="close">Cancel</btn>
      <btn kind="primary" @click="save">Save</btn>
    </template>
  </modal-headless>
</template>
<script>
import ModalHeadless from '../../ModalHeadless';
import AddressFields from './AddressFields';

export default {
  components: {AddressFields, ModalHeadless},
  data() {
    return {
      address: {
        title: 'Test title',
        countryCode: null,
      }
    }
  },

  props: {
    isOpen: {
      type: Boolean,
      default: false,
    },
    editAddress: {
      type: Object,
      default: null,
    }
  },

  watch: {
    editAddress() {
      this.address = JSON.parse(JSON.stringify(this.editAddress))
      console.log('edit address change', this.address.id)
    }
  },

  methods: {
    save() {
      this.$store.dispatch('addresses/saveAddress', this.address)
        .then(() => {
          this.$emit('close')
        })
    },
    close() {
      this.$emit('close')
      this.$store.commit('addresses/updateInfo', {info: null})
    },
  },
}
</script>