<template>
  <div>
    <h1>Buy Plugin</h1>

    <pane>
      <div class="mr-4">
        Adding <code>{{ handle }}</code> to your cart…
      </div>

      <spinner v-if="loading"></spinner>
    </pane>
  </div>
</template>

<script>
export default {
  data() {
    return {
      loading: false,
    }
  },

  computed: {
    handle() {
      return this.$route.params.handle
    },

    edition() {
      return this.$route.params.edition
    }
  },

  methods: {
    addToCart() {
      this.loading = true

      const item = {
        type: 'plugin-edition',
        plugin: this.handle,
        edition: this.edition,
        autoRenew: false,
      }

      this.$store.dispatch('cart/addToCart', [item])
        .then(() => {
          this.loading = false
          this.$store.dispatch('app/displayNotice', 'Plugin license added your cart.')
          this.$router.push({path: '/cart'})
        })
    }
  },

  mounted() {
    this.addToCart()
  }
}
</script>
