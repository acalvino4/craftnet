<template>
  <modal-headless
    :isOpen="isOpen"
    @close="$emit('close')"
  >
    <div>
      <h2>
        <template v-if="!paymentMethod">
          Add Card
        </template>
        <template v-else>
          Edit payment method #{{paymentMethod.id}}
        </template>
      </h2>

      <template v-if="!paymentMethod">
        <form class="mt-2" @submit.prevent="save()">
          <card-element
            ref="cardElement"
            v-model:card="card"
          />

          <div>
            <img
              src="~@/console/images/powered_by_stripe.svg"
              width="90" />
          </div>
        </form>
      </template>
      <template v-else>
        <div class="mt-2 border p-4 rounded-md">
          <div>{{ paymentMethod.card.brand }}</div>
          <div>**** **** **** {{ paymentMethod.card.last4 }}</div>
          <div>{{ paymentMethod.card.exp_month }}/{{ paymentMethod.card.exp_year }}</div>
        </div>
      </template>

      <h3 class="mt-4">Billing</h3>
      <div>
        <billing-address-options
          v-model:billingAddressId="billingAddressId"
        />
      </div>
    </div>

    <template v-slot:footer>
      <div class="flex-1 flex items-center justify-end">
        <template v-if="paymentMethod">
          <div
            class="flex-1"
            :class="{
            'opacity-50': cardFormloading,
          }"
          >
            <a href="#" class="text-red-600" @click.prevent="removePaymentMethod(paymentMethod.id)">Remove card</a>
          </div>
        </template>

        <div class="flex items-center space-x-2">
          <spinner
            v-if="cardFormloading"
          />

          <btn :disabled="cardFormloading" @click="cancel">Cancel</btn>
          <btn :disabled="cardFormloading" kind="primary" @click="save">Save</btn>
        </div>
      </div>
    </template>
  </modal-headless>
</template>

<script>
import ModalHeadless from '../ModalHeadless';
import CardElement from '../card/CardElement';
import BillingAddressOptions from './BillingAddressOptions';

export default {
  components: {
    BillingAddressOptions,
    ModalHeadless,
    CardElement,
  },

  props: {
    isOpen: {
      type: Boolean,
      default: false,
    },
    paymentMethod: {
      type: Object,
      default: null,
    },
  },

  watch: {
    paymentMethod(paymentMethod) {
      this.billingAddressId = paymentMethod ? paymentMethod.billingAddressId : null
    }
  },

  data() {
    return {
      stripe: null,
      elements: null,
      card: null,
      cardFormloading: false,
      billingAddressId: null,
    }
  },

  computed: {
    localPaymentMethod: {
      get() {
        return this.paymentMethod
      },
      set(value) {
        this.$emit('update:paymentMethod', value)
      }
    }
  },

  methods: {
    /**
     * Save the credit card.
     */
    save() {
      this.cardFormloading = true

      if (!this.paymentMethod) {
        this._saveCard()
          .then((result) => {
            this._savePaymentMethod({
              id: (this.paymentMethod ? this.paymentMethod.id : null),
              paymentMethodId: result.paymentMethod.id,
              billingAddressId: this.billingAddressId,
            })
          })
      } else {
        this._savePaymentMethod({
          id: (this.paymentMethod ? this.paymentMethod.id : null),
          billingAddressId: this.billingAddressId,
        })
      }
    },

    _saveCard() {
      return this.$refs.cardElement.save()
        // .catch(() => {
        //   this.cardFormloading = false
        //   this.$store.dispatch('app/displayError', 'Couldn’t save credit card.')
        // })
    },

    _savePaymentMethod(payload) {
      return this.$store.dispatch('paymentMethods/savePaymentMethod', payload)
        .then(() => {
          if (this.$refs.cardElement) {
            this.$refs.cardElement.card.clear()
          }

          this.cardFormloading = false
          this.$store.dispatch('app/displayNotice', 'Card saved.')
          this.$store.dispatch('paymentMethods/getPaymentMethods')
          this.$emit('close')
        })
        // .catch((response) => {
        //   this.cardFormloading = false
        //   const errorMessage = response && response.data && response.data.error ? response.data.error : 'Couldn’t save credit card.'
        //   this.$store.dispatch('app/displayError', errorMessage)
        // })
    },

    /**
     * Cancel.
     */
    cancel() {
      this.$emit('close')
    },

    /**
     * Error.
     */
    error() {
      this.cardFormloading = false
    },

    /**
     * Removes a payment method.
     */
    removePaymentMethod(paymentMethodId) {
      if (!confirm("Are you sure you want to remove this credit card?")) {
        return null;
      }

      this.cardFormloading = true
      this.$store.dispatch('paymentMethods/removePaymentMethod', paymentMethodId)
        .then(() => {
          this.cardFormloading = false
          this.$store.dispatch('app/displayNotice', 'Card removed.')
          this.$emit('close')
        })
        .catch((response) => {
          this.cardFormloading = false
          const errorMessage = response.data && response.data.error ? response.data.error : 'Couldn’t remove credit card.'
          this.$store.dispatch('app/displayError', errorMessage)
        })
    },
  },
}
</script>

<style
  lang="scss"
  scoped>
.card-element {
  @apply border border-separator px-3 py-2 rounded;
  max-width: 410px;
}
</style>
