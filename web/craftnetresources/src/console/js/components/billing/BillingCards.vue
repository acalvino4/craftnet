<template>
  <div>
    <h2>Credit Cards</h2>

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
            <div>
              <icon
                icon="plus"
                class="w-4 h-4" />

              Add a credit card
            </div>
          </a>
          <template v-for="(paymentMethod, paymentMethodKey) in paymentMethods" :key="paymentMethodKey">
            <div class="border border-gray-200 dark:border-gray-700 rounded-md p-4">
              <div>
                <div class="flex">
                  <div class="mr-4">
                    <icon
                      icon="credit-card"
                      class="w-8 h-8 text-gray-500" />
                  </div>

                  <div>
                    <template v-if="paymentMethod.isPrimary">
                      <div class="mb-2">
                        <badge>Primary</badge>
                      </div>
                    </template>

                    <div>
                      {{ paymentMethod.card.brand }}
                    </div>

                    <div>
                      **** **** **** {{ paymentMethod.card.last4 }}
                    </div>

                    <div class="text-sm text-gray-600">
                      {{ paymentMethod.card.exp_month }}/{{ paymentMethod.card.exp_year }}
                    </div>

                    <div class="mt-4 space-x-4">
                      <template v-if="!paymentMethod.isPrimary">
                        <a href="#" @click.prevent="makePrimary(paymentMethod.id)">Make primary</a>
                      </template>
                      <a href="#" @click.prevent="removePaymentMethod(paymentMethod.id)">Remove</a>
                    </div>


                    <div class="mt-4">
                      <div class="text-xs font-mono text-gray-500">id: #{{paymentMethod.id}}</div>
                      <div class="text-xs font-mono text-gray-500">billingAddressId: #{{paymentMethod.billingAddressId}}</div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </template>
        </div>
      </div>

      <payment-method-modal
        :is-open="showPaymentMethodModal"
        @close="showPaymentMethodModal = false"
      />
    </template>
  </div>
</template>

<script>
import {mapState} from 'vuex'
import helpers from '../../mixins/helpers.js'
import PaymentMethodModal from './PaymentMethodModal';

export default {
  components: {PaymentMethodModal},
  mixins: [helpers],

  data() {
    return {
      loading: false,
      removePaymentLoading: false,
      showPaymentMethodModal: false,
    }
  },

  computed: {
    ...mapState({
      paymentMethods: state => state.paymentMethods.paymentMethods,
    }),
  },

  methods: {
    /**
     * Removes a payment method.
     */
    removePaymentMethod(paymentMethodId) {
      if (!confirm("Are you sure you want to remove this credit card?")) {
        return null;
      }

      this.removePaymentLoading = true
      this.$store.dispatch('paymentMethods/removePaymentMethod', paymentMethodId)
        .then(() => {
          this.removePaymentLoading = false
          this.$store.dispatch('app/displayNotice', 'Card removed.')
        })
        .catch((response) => {
          this.removePaymentLoading = false
          const errorMessage = response.data && response.data.error ? response.data.error : 'Couldn’t remove credit card.'
          this.$store.dispatch('app/displayError', errorMessage)
        })
    },

    /**
     * Makes a credit card primary.
     */
    makePrimary(paymentMethodId) {
      this.$store.dispatch('paymentMethods/savePaymentMethod', {
        paymentMethodId,
        card: {
          makePrimary: true,
        }
      })
        .then(() => {
          this.$store.dispatch('app/displayNotice', 'Card set as primary.')
          this.$store.dispatch('paymentMethods/getPaymentMethods')
        })
    }
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
