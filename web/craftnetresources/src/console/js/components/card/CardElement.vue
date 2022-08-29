<template>
  <div>
    <div
      ref="cardElement"
      class="card-element form-control mb-3"></div>
    <p
      id="card-errors"
      class="text-red-600"
      role="alert"></p>
  </div>
</template>

<script>
/* global Stripe */

export default {
  props: {
    card: {
      type: Object,
      default: () => ({})
    }
  },

  data() {
    return {
      stripe: null,
      elements: null,
      localCard: null,
      mode: 'paymentMethod',
    }
  },

  watch: {
    localCard(card) {
      this.$emit('update:card', card)
    }
  },

  methods: {
    /**
     * Save the credit card.
     */
    save() {
      return new Promise((resolve, reject) => {
        if (this.mode === 'source') {
          this.stripe.createSource(this.localCard)
            .then(function(result) {
              if (result.error) {
                let errorElement = document.getElementById('card-errors');
                errorElement.textContent = result.error.message;
                reject(result.error)
              } else {
                resolve(result)
              }
            });
        } else {
          this.stripe.createPaymentMethod('card', this.localCard)
            .then(function(result) {
              if (result.error) {
                let errorElement = document.getElementById('card-errors');
                errorElement.textContent = result.error.message;
                reject(result.error)
              } else {
                resolve(result)
              }
            })
        }
      })
    },
  },

  mounted() {
    this.stripe = Stripe(window.stripePublicKey);
    this.elements = this.stripe.elements({locale: 'en'});
    this.localCard = this.elements.create('card', {
      hidePostalCode: true,
      style: {
        base: {
          fontSize: '16px',
          lineHeight: '24px',

        }
      }
    });

    // Vue likes to stay in control of $el but Stripe needs a real element
    const el = document.createElement('div')
    this.localCard.mount(el)

    // this.$children cannot be used because it expects a VNode :(
    this.$refs.cardElement.appendChild(el)
  },
}
</script>

<style
  lang="scss"
>
.card-element {
  @apply border border-gray-300 dark:border-gray-600 px-3 py-2 rounded;
  @apply max-w-md;
}
</style>
