<template>
  <div>
    <page-header>
      <div class="flex-1 flex">
        <div class="flex-1">
          <template v-if="user">
            <router-link to="/cart">← Cart</router-link>
          </template>
          <template v-else>
            <router-link to="/identity">← Identity</router-link>
          </template>

          <h1 class="mt-4">How would you like to pay?</h1>
        </div>

        <div class="space-x-4">
          <router-link to="/payment/old">Old</router-link>
        </div>
      </div>
    </page-header>

    <new-payment-method
      v-model:replaceCard="replaceCard"
    />
  </div>
</template>
<script>
import PageHeader from '../../components/PageHeader';
import NewPaymentMethod from '../../components/payment/NewPaymentMethod';
import {mapState} from 'vuex';
export default {
  components: {NewPaymentMethod, PageHeader},

  data() {
    return {
      replaceCard: false,
    }
  },

  computed: {
    ...mapState({
      user: state => state.account.user,
    }),
  }
}
</script>