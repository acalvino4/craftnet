<template>
  <div>
    <template v-if="loading || cartIsEmpty">
      <spinner></spinner>
    </template>
    <template v-else>
      <page-header>
        <div>
          <p>
            <router-link to="/cart">← Cart</router-link>
          </p>
          <h1>Identity</h1>
        </div>
      </page-header>

      <div class="flex mt-8">
        <div class="flex-1 pr-8">
          <h2 class="mb-2">Use your Craft ID</h2>
          <p>
            <router-link to="/login">Login</router-link>
            or
            <router-link to="/register">register</router-link>
            to purchase licenses with your Craft ID.
          </p>
        </div>

        <div class="flex-1 pl-8 border-l">
          <h2 class="mb-2">Continue as guest</h2>
          <form
            class="space-y-4"
            @submit.prevent="submit">
            <textbox
              v-model="guestEmail"
              placeholder="Enter your email address" />
            <btn
              type="submit"
              kind="primary"
              :loading="guestLoading"
              :disabled="v$.guestEmail.$invalid">Continue as
              guest
            </btn>
          </form>
        </div>
      </div>
    </template>
  </div>
</template>

<script>
import {mapState, mapActions, mapGetters} from 'vuex'
import useVuelidate from '@vuelidate/core'
import {email, required} from '@vuelidate/validators'
import PageHeader from '@/console/js/components/PageHeader';

export default {
  components: {PageHeader},
  setup() {
    return {v$: useVuelidate()}
  },

  data() {
    return {
      loading: false,
      identityMode: 'craftid',
      guestEmail: null,
      guestLoading: false,
    }
  },

  validations: {
    guestEmail: {required, email},
  },

  computed: {
    ...mapState({
      cart: state => state.cart.cart,
      user: state => state.account.user,
    }),

    ...mapGetters({
      cartIsEmpty: 'cart/cartIsEmpty',
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
        if (this.cartIsEmpty) {
          this.$router.push('/cart')
        }

        this.loading = false
        this.guestEmail = this.cart.email
      })
      .catch(() => {
        this.loading = false
      })
  }
}
</script>