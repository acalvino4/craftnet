<template>
  <div>
    <div class="flex">
      <div class="flex-1">
        <h2>Payment</h2>

        <template v-if="loading">
          <spinner class="mt-3"></spinner>
        </template>
        <template v-else>
          <div v-if="!editing">
            <div
              v-if="card"
              class="credit-card">
              <card-icon :brand="card.brand"></card-icon>
              <ul>
                <li>Number: •••• •••• •••• {{ card.last4 }}</li>
                <li>Expiry: {{
                    card.exp_month
                  }}/{{ card.exp_year }}
                </li>
              </ul>
            </div>

            <p
              v-else
              class="text-light">Add a credit card and use
              Craft ID to purchase licenses and renewals.</p>
          </div>

          <div :class="{'hidden': !editing}">
            <card-form
              :loading="cardFormloading"
              @error="error"
              @beforeSave="beforeSave"
              @save="saveCard"
              @cancel="cancel"></card-form>

            <div class="mt-4">
              <img
                src="~@/console/images/powered_by_stripe.svg"
                width="90" />
            </div>
          </div>
        </template>
      </div>

      <div
        v-if="!loading && !editing"
        class="pl-4">
        <p>
          <template v-if="card">
            <btn
              small
              @click="editing = true">Change card
            </btn>
          </template>
          <template v-else>
            <btn
              small
              @click="editing = true">
              <icon
                icon="plus"
                class="w-4 h-4" />
              Add a card
            </btn>
          </template>
        </p>

        <p v-if="card">
          <btn
            kind="danger"
            small
            @click="removeCard()">
            <icon
              icon="x"
              class="w-4 h-4" />
            Remove
          </btn>
          <spinner v-if="removeCardLoading"></spinner>
        </p>
      </div>
    </div>
  </div>
</template>

<script>
import {mapState} from 'vuex'
import CardForm from '../card/CardForm'
import CardIcon from '../card/CardIcon'
import helpers from '../../mixins/helpers.js'

export default {

  mixins: [helpers],

  components: {
    CardForm,
    CardIcon,
  },

  data() {
    return {
      loading: false,
      editing: false,
      cardFormloading: false,
      removeCardLoading: false,
    }
  },

  computed: {

    ...mapState({
      card: state => state.stripe.card,
    }),

  },

  methods: {

    /**
     * Saves a credit card.
     *
     * @param card
     * @param source
     */
    saveCard(card, source) {
      this.$store.dispatch('stripe/saveCard', source)
        .then(() => {
          card.clear()
          this.cardFormloading = false
          this.editing = false
          this.$store.dispatch('app/displayNotice', 'Card saved.')
        })
        .catch((response) => {
          this.cardFormloading = false
          const errorMessage = response.data && response.data.error ? response.data.error : 'Couldn’t save credit card.'
          this.$store.dispatch('app/displayError', errorMessage)
        })
    },

    /**
     * Removes a credit card.
     */
    removeCard() {
      this.removeCardLoading = true
      this.$store.dispatch('stripe/removeCard')
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

    /**
     * Before save.
     */
    beforeSave() {
      this.cardFormloading = true
    },

    /**
     * Cancel changes.
     */
    cancel() {
      this.editing = false
    },

    /**
     * Error.
     */
    error() {
      this.cardFormloading = false
    },

  },

  mounted() {
    this.loading = true

    this.$store.dispatch('stripe/getStripeAccount')
      .then(() => {
        this.loading = false
      })
      .catch(() => {
        this.loading = false
        this.$store.dispatch('app/displayNotice', 'Couldn’t get Stripe account.')
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
