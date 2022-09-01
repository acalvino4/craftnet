<template>
  <div>
    <h2>Payment Methods</h2>

    <template v-if="loading">
      <spinner class="mt-3"></spinner>
    </template>
    <template v-else>
      <div class="mt-2 space-y-4">
        <div class="grid grid-cols-3 gap-4">
          <a
            href="#"
            class="border border-dashed border-gray-300 dark:border-gray-600 rounded-md p-4 flex items-center justify-center"
            @click.prevent="showPaymentMethodModal = true"
          >
            <div class="text-center">
              <icon
                icon="plus"
                class="w-4 h-4"
              />
              Add a payment method
            </div>
          </a>
          <template v-for="(paymentMethod, paymentMethodKey) in paymentMethods" :key="paymentMethodKey">
            <payment-method
              :paymentMethod="paymentMethod"
              @edit="edit(paymentMethod)"
              @makePrimary="makePrimary(paymentMethod.id)"
            />
          </template>
        </div>
      </div>

      <payment-method-modal
        :is-open="showPaymentMethodModal"
        v-model:paymentMethod="editPaymentMethod"
        @close="closePaymentMethodModal"
      />
    </template>
  </div>
</template>

<script>
import {mapState} from 'vuex'
import helpers from '../../mixins/helpers.js'
import PaymentMethodModal from './PaymentMethodModal';
import PaymentMethod from './PaymentMethod';

export default {
  components: {PaymentMethod, PaymentMethodModal},
  mixins: [helpers],

  data() {
    return {
      loading: false,
      showPaymentMethodModal: false,
      editPaymentMethod: null,
    }
  },

  computed: {
    ...mapState({
      paymentMethods: state => state.paymentMethods.paymentMethods,
    }),
  },

  methods: {
    /**
     * Makes a credit card primary.
     */
    makePrimary(paymentMethodId) {
      this.$store.dispatch('paymentMethods/savePaymentMethod', {
        id: paymentMethodId,
        makePrimary: true,
      })
        .then(() => {
          this.$store.dispatch('app/displayNotice', 'Card set as primary.')
          this.$store.dispatch('paymentMethods/getPaymentMethods')
        })
    },

    edit(paymentMethod) {
      this.showPaymentMethodModal = true
      this.editPaymentMethod = paymentMethod
    },

    closePaymentMethodModal() {
      this.showPaymentMethodModal = false
      this.editPaymentMethod = null
    },
  },

  mounted() {
    this.loading = true

    this.$store.dispatch('paymentMethods/getPaymentMethods')
      .then(() => {
        this.loading = false
      })
      .catch(() => {
        this.loading = false
        this.$store.dispatch('app/displayNotice', 'Couldn’t get credit cards.')
      })
  }

}
</script>

<style lang="scss">
.credit-card {
  .card-icon {
    @apply mb-1;
  }
}
</style>
