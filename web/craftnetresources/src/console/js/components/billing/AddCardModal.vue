<template>
  <modal-headless
    :isOpen="isOpen"
    @close="$emit('close')"
  >
    <div>
      <h2>Add Card</h2>

      <form class="mt-2" @submit.prevent="save()">
        <div
          ref="cardElement"
          class="card-element form-control mb-3"></div>
        <p
          id="card-errors"
          class="text-red"
          role="alert"></p>

        <div>
          <img
            src="~@/console/images/powered_by_stripe.svg"
            width="90" />
        </div>

        <spinner
          v-if="cardFormloading"
          class="mt-4"
        />
      </form>
    </div>

    <template v-slot:footer>
      <btn @click="cancel">Cancel</btn>
      <btn kind="primary" @click="save">Save</btn>
    </template>
  </modal-headless>
</template>

<script>
/* global Stripe */

import ModalHeadless from '../ModalHeadless';

export default {
  components: {
    ModalHeadless,
  },

  props: {
    isOpen: {
      type: Boolean,
      default: false,
    },
  },

  data() {
    return {
      stripe: null,
      elements: null,
      card: null,
      cardFormloading: false,
    }
  },

  methods: {
    /**
     * Saves a credit card.
     *
     * @param card
     * @param source
     */
    saveCardForm(card, source) {
      this.$store.dispatch('stripe/addCard', source)
        .then(() => {
          card.clear()
          this.cardFormloading = false
          this.$store.dispatch('app/displayNotice', 'Card saved.')
          this.$store.dispatch('stripe/getCards')
          this.$emit('close')
        })
        .catch((response) => {
          this.cardFormloading = false
          const errorMessage = response && response.data && response.data.error ? response.data.error : 'Couldn’t save credit card.'
          this.$store.dispatch('app/displayError', errorMessage)
        })
    },

    /**
     * Save the credit card.
     */
    save() {
      this.cardFormloading = true

      let vm = this;
      this.stripe.createPaymentMethod('card', this.card).then(function(result) {
        if (result.error) {
          let errorElement = document.getElementById('card-errors');
          errorElement.textContent = result.error.message;

          vm.cardFormloading = false
          const errorMessage = result && result.error ? result.error : 'Couldn’t save credit card.'
          vm.$store.dispatch('app/displayError', errorMessage)
        } else {
          // vm.$emit('save', vm.card, result.paymentMethod);
          vm.saveCardForm(vm.card, result.paymentMethod)
        }
      });
    },

    /**
     * Cancel.
     */
    cancel() {
      this.card.clear();

      let errorElement = document.getElementById('card-errors');
      errorElement.textContent = '';

      this.$emit('close')
    },

    /**
     * Error.
     */
    error() {
      this.cardFormloading = false
    },
  },

  watch: {
    isOpen(isOpen) {
      if (isOpen) {
        console.log('-----------', isOpen, this.$refs.cardElement)

        // wait for the card element to be mounted
        this.$nextTick(() => {
          console.log('---- next tick ----', this.$refs.cardElement)

          this.stripe = Stripe(window.stripePublicKey);
          this.elements = this.stripe.elements({locale: 'en'});
          this.card = this.elements.create('card', {hidePostalCode: true});

          // Vue likes to stay in control of $el but Stripe needs a real element
          const el = document.createElement('div')
          this.card.mount(el)

          // this.$children cannot be used because it expects a VNode :(
          this.$refs.cardElement.appendChild(el)
        })
      }
    }
  },

  mounted() {
    console.log('mounted', this.$refs.cardElement)
  }
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
