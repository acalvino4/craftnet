<template>
  <div>
    <div
      ref="cardElement"
      class="card-element form-control mb-3"></div>
    <p
      id="card-errors"
      class="text-red"
      role="alert"></p>
  </div>
</template>

<script>
/* global Stripe */

export default {
  data() {
    return {
      stripe: null,
      elements: null,
      card: null,
    }
  },

  methods: {
    /**
     * Save the credit card.
     */
    save(cb, cbError) {
      this.stripe.createSource(this.card)
        .then(function(result) {
          if (result.error) {
            let errorElement = document.getElementById('card-errors');
            errorElement.textContent = result.error.message;
            cbError(result.error)
          } else {
            cb(result.source)
          }
        });
    },
  },

  mounted() {
    this.stripe = Stripe(window.stripePublicKey);
    this.elements = this.stripe.elements({locale: 'en'});
    this.card = this.elements.create('card', {
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
    this.card.mount(el)

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
