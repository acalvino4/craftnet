<template>
  <div>
    <template v-if="loading">
      <spinner></spinner>
    </template>
    <template v-else>
      <p>
        <router-link to="/cart">← Cart</router-link>
      </p>
      <h1>Identity</h1>

      <h2>Use your Craft ID</h2>
      <p>
        <router-link to="/login">Login</router-link>
        or
        <router-link to="/register">register</router-link>
        to purchase licenses with your Craft ID.
      </p>

      <h2>Continue as guest</h2>
      <form @submit.prevent="submit">
        <textbox
          v-model="guestEmail"
          placeholder="Enter your email address" />
        <btn
          type="submit"
          kind="primary"
          :loading="guestLoading"
          :disabled="$v.guestEmail.$invalid">Continue as guest
        </btn>
      </form>
    </template>
  </div>
</template>

<script>
import {mapState, mapActions} from 'vuex'
import {required, email} from 'vuelidate/lib/validators'

export default {
  data() {
    return {
      loading: false,
      identityMode: 'craftid',
      guestEmail: null,
      guestLoading: false,
    }
  },

  validations: {
    guestEmail: {
      required,
      email,
    },
  },

  computed: {
    ...mapState({
      cart: state => state.cart.cart,
      user: state => state.account.user,
    }),
  },

  methods: {
    ...mapActions({
      getCart: 'cart/getCart',
    }),

    submit() {
      this.guestLoading = true

      let cartData = {
        email: this.guestEmail
      }

      this.$store.dispatch('cart/saveCart', cartData)
        .then(() => {
          this.guestLoading = false
          this.$router.push({path: '/payment'})
        })
        .catch((error) => {
          this.guestLoading = false
          const errorMessage = error.response.data && error.response.data.error ? error.response.data.error : 'Couldn’t continue as guest.'
          this.$store.dispatch('app/displayError', errorMessage)
        })
    }
  },

  mounted() {
    if (this.user) {
      this.$router.push({path: '/payment'})
      return
    }

    this.loading = true

    this.getCart()
      .then(() => {
        this.loading = false
        this.guestEmail = this.cart.email
      })
      .catch(() => {
        this.loading = false
      })
  }
}
</script>