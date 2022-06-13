<template>
  <div>
    <h1>Buy Craft CMS</h1>
    <p>Adding Craft CMS {{ edition }} edition to your cart…</p>
    <spinner v-if="loading"></spinner>
  </div>
</template>

<script>
export default {
  data() {
    return {
      loading: true,
      type: 'cms-edition',
    }
  },

  computed: {
    edition() {
      return this.$route.params.edition
    }
  },

  methods: {
    addToCart() {
      this.loading = true

      const item = {
        type: 'cms-edition',
        edition: this.edition,
        autoRenew: false,
      }

      this.$store.dispatch('cart/addToCart', [item])
        .then(() => {
          this.loading = false
          this.$store.dispatch('app/displayNotice', 'Craft CMS license added your cart.')
          this.$router.push({path: '/cart'})
        })
    }
  },

  mounted() {
    this.addToCart()
  }
}
</script>
