<template>
  <page-alert
    v-if="!loading && !stripeAccount"
    type="warning">
    <strong>Stripe account missing.</strong>
    Define a Stripe account in your
    <router-link to="/settings/apps">apps</router-link>
    .
  </page-alert>
</template>

<script>
import {mapState} from 'vuex'
import PageAlert from './PageAlert'

export default {
  data() {
    return {
      loading: false,
    }
  },

  components: {
    PageAlert,
  },

  computed: {
    ...mapState({
      stripeAccount: state => state.stripe.stripeAccount,
    }),
  },

  mounted() {
    this.loading = true

    this.$store.dispatch('stripe/getStripeAccount')
      .then(() => {
        this.loading = false
      })
      .catch(() => {
        this.loading = false
      })
  }
}
</script>
