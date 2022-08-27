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
            @click.prevent="showAddCardModal = true"
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
                        <a href="#" @click.prevent="setPrimary(paymentMethod.id)">Set as primary</a>
                      </template>
                      <a href="#" @click.prevent="removeCard(paymentMethod.id)">Remove</a>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </template>
        </div>
      </div>

      <add-card-modal
        :is-open="showAddCardModal"
        @close="showAddCardModal = false"
      />
    </template>
  </div>
</template>

<script>
import {mapState} from 'vuex'
import helpers from '../../mixins/helpers.js'
import AddCardModal from './AddCardModal';

export default {
  components: {AddCardModal},
  mixins: [helpers],

  data() {
    return {
      loading: false,
      removeCardLoading: false,
      showAddCardModal: false,
    }
  },

  computed: {
    ...mapState({
      paymentMethods: state => state.stripe.paymentMethods,
    }),
  },

  methods: {
    /**
     * Removes a credit card.
     */
    removeCard(cardId) {
      if (!confirm("Are you sure you want to remove this credit card?")) {
        return null;
      }

      this.removeCardLoading = true
      this.$store.dispatch('paymentMethods/removeCard', cardId)
        .then(() => {
          this.removeCardLoading = false
          this.$store.dispatch('app/displayNotice', 'Card removed.')
        })
        .catch((response) => {
          this.removeCardLoading = false
          const errorMessage = response.data && response.data.error ? response.data.error : 'Couldn’t remove credit card.'
          this.$store.dispatch('app/displayError', errorMessage)
        })
    },

    setPrimary(cardId) {
      this.$store.dispatch('paymentMethods/saveCard', {
        paymentSourceId: cardId,
        card: {
          isPrimary: true,
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
