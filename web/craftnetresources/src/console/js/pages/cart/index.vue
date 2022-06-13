<template>
  <div>
    <page-header>
      <h1>Cart</h1>
      <div class="space-x-4">
        <router-link to="/cart/old">Old</router-link>
      </div>
    </page-header>

    <spinner v-if="loading"></spinner>

    <template v-else>
      <template v-if="cart">
        <template v-if="cartItems.length">
          <div>
            <div class="mt-6">
              <!-- Items -->
              <template
                v-for="(item, itemKey) in cartItems"
                :key="'items-' + itemKey">
                <item
                  :item="item"
                  :item-key="itemKey"></item>
              </template>

              <!-- Total -->
              <div class="border-t pt-6">
                <div class="md:ml-32 flex justify-between">
                  <div class="font-bold text-xl">
                    Your total
                  </div>
                  <div class="text-right text-xl"><strong>{{
                      $filters.currency(cart.totalPrice)
                    }}</strong></div>
                </div>
              </div>

              <!-- Checkout button -->
              <div class="mt-4 py-4 text-right">
                <btn
                  kind="primary"
                  class="w-full md:px-16 md:w-auto"
                  large
                  @click="checkout()">Check
                  Out
                </btn>
              </div>
            </div>
          </div>
        </template>

        <div v-else>
          <!-- Empty cart -->
          <empty>
            <icon
              set="outline"
              icon="shopping-cart"
              class="w-12 h-12 mb-4 text-blue-500" />

            <div class="font-bold">Your cart is empty</div>
            <div class="mt-2">
              <p>Browse plugins on the <a
                :href="craftPluginsUrl()">Plugin Store</a>.</p>
            </div>
          </empty>
        </div>
      </template>
    </template>
  </div>
</template>

<script>
import {mapState, mapGetters, mapActions} from 'vuex'
import Empty from '@/console/js/components/Empty'
import helpers from '@/console/js/mixins/helpers.js'
import PageHeader from '@/console/js/components/PageHeader'
import Icon from '@/common/ui/components/Icon';
import Item from '@/console/js/components/cart/Item';

export default {
  mixins: [helpers],

  components: {
    Item,
    Icon,
    Empty,
    PageHeader,
  },

  data() {
    return {
      loading: false,
    }
  },

  computed: {
    ...mapState({
      cart: state => state.cart.cart,
      user: state => state.account.user,
    }),

    ...mapGetters({
      cartItems: 'cart/cartItems',
    }),
  },

  methods: {
    ...mapActions({
      getCart: 'cart/getCart',
      getCoreData: 'pluginStore/getCoreData',
    }),

    checkout() {
      if (!this.user) {
        this.$router.push({path: '/identity'})
        return
      }

      this.$router.push({path: '/payment'})
    },
  },

  mounted() {
    this.loading = true

    // Set new cart based on orderNumber if passed
    if (this.$route.query.orderNumber) {
      const orderNumber = this.$route.query.orderNumber
      this.$store.commit('cart/resetCart')
      this.$store.dispatch('cart/saveOrderNumber', {orderNumber})
      this.$router.push('/cart')
    }

    this.getCoreData()
      .then(() => {
        this.getCart()
          .then(() => {
            this.loading = false

            this.cartItems.forEach(function(item, key) {
              this.$store.commit('cart/updateItemQuantity', {
                itemKey: key,
                value: 1,
              })

            }.bind(this))
          })
          .catch(() => {
            this.loading = false
          })
      })
      .catch(() => {
        this.loading = false
      })
  },
}
</script>

<style lang="scss">
.cart-data {
  td.description {
    strong {
      @apply mr-2 text-xl;
    }

    .edition-badge {
      @apply relative;
      top: -2px;
    }
  }

  .expiry-date {
    @apply w-2/5;

    .expiry-date-flex {
      @apply flex flex-row items-center;

      .c-field {
        @apply mb-0;
      }
    }
  }

  .c-spinner {
    @apply ml-4;
  }
}
</style>
