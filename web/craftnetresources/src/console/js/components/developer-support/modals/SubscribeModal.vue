<template>
  <modal-headless
    :isOpen="show"
    @close="$emit('close')">
    <div v-if="selectedPlan">
      <template v-if="subscriptionMode === 'subscribe'">
        <h2>Subscribe to this support plan</h2>
      </template>
      <template v-else-if="selectedPlan.price > currentPlan.price">
        <h2>Upgrade support plan</h2>
      </template>
      <template v-else>
        <h2>Switch support plan</h2>
        <p>Your plan will switch to the {{ selectedPlan.name }} tier at
          the end of the billing cycle.</p>
      </template>

      <template v-if="!card">
        <p>You must add a credit card to your account at <a
          @click="goToBilling">Account → Billing</a> before signing up
          for a support plan.</p>
      </template>

      <table class="table border-b mt-6">
        <thead class="hidden">
        <tr>
          <th>Item</th>
          <th>Price</th>
        </tr>
        </thead>
        <tbody>
        <tr>
          <td>Current Plan</td>
          <td class="text-right">{{ currentPlan.name }}</td>
        </tr>
        <tr>
          <td>New Plan</td>
          <td class="text-right">{{ selectedPlan.name }}</td>
        </tr>
        <tr>
          <td>Price</td>
          <td class="text-right">
            {{ $filters.currency(subscriptionInfoPlan.cost.switch) }}<br />
            <small class="text-grey-dark">Then
              {{ $filters.currency(subscriptionInfoPlan.cost.recurring) }}
              every month</small>
          </td>
        </tr>
        </tbody>
      </table>
    </div>

    <template v-slot:footer>
      <div class="flex justify-end items-center space-x-2">
        <template v-if="!loading">
          <spinner class="mr-2"></spinner>
        </template>
        <btn
          ref="cancelBtn"
          :disabled="loading"
          @click="$emit('close')">Cancel
        </btn>
        <template v-if="subscriptionMode === 'subscribe'">
          <btn
            ref="submitBtn"
            kind="primary"
            :disabled="!card || loading"
            @click="subscribePlan()">
            Subscribe to this plan
          </btn>
        </template>
        <template v-else>
          <btn
            ref="submitBtn"
            kind="primary"
            :disabled="!card || loading"
            @click="switchPlan()">
            <template v-if="selectedPlan.price > currentPlan.price">
              Upgrade plan
            </template>
            <template v-else>
              Switch plan
            </template>
          </btn>
        </template>
      </div>
    </template>
  </modal-headless>
</template>

<script>
import {mapState, mapGetters} from 'vuex'
import ModalHeadless from '@/console/js/components/ModalHeadless';

export default {
  props: ['show', 'selectedPlan'],

  components: {
    ModalHeadless,
  },

  data() {
    return {
      loading: false,
    }
  },

  watch: {
    show(show) {
      if (show) {
        this.$nextTick(() => {
          if (this.$refs.submitBtn) {
            this.$refs.submitBtn.$el.focus()
          }
        })
      }
    }
  },

  computed: {
    ...mapState({
      card: state => state.stripe.card,
      plans: state => state.developerSupport.plans,
    }),

    ...mapGetters({
      currentPlan: 'developerSupport/currentPlan',
    }),

    subscriptionInfoPlan() {
      return this.$store.getters['developerSupport/subscriptionInfoPlan'](this.selectedPlan.handle)
    },

    subscriptionMode() {
      const proSubscription = this.$store.getters['developerSupport/subscriptionInfoSubscriptionData']('pro')
      const premiumSubscription = this.$store.getters['developerSupport/subscriptionInfoSubscriptionData']('premium')

      switch (this.selectedPlan.handle) {
        case 'pro':
          if ((proSubscription.status === 'inactive' && premiumSubscription.status === 'inactive') || premiumSubscription.status === 'expiring') {
            return 'subscribe'
          }
          break
        case 'premium':
          if ((proSubscription.status === 'inactive' && premiumSubscription.status === 'inactive')) {
            return 'subscribe'
          }
          break
      }

      return 'switch'
    }
  },

  methods: {
    switchPlan() {
      if (!this.card) {
        return null
      }

      this.loading = true

      this.$store.dispatch('developerSupport/switchPlan', this.selectedPlan.handle)
        .then(() => {
          this.loading = false
          this.$store.dispatch('app/displayNotice', 'Support plan switched to ' + this.selectedPlan.handle + '.')
          this.$emit('close')
        })
        .catch((error) => {
          this.loading = false
          const errorMessage = error ? error : 'Couldn’t switch support plan.'
          this.$store.dispatch('app/displayError', errorMessage)
          this.$emit('close')
        })
    },

    subscribePlan() {
      if (!this.card) {
        return null
      }

      this.loading = true

      this.$store.dispatch('developerSupport/subscribe', this.selectedPlan.handle)
        .then(() => {
          this.loading = false
          this.$store.dispatch('app/displayNotice', 'Subscribed to ' + this.selectedPlan.handle + ' plan.')
          this.$emit('close')
        })
        .catch((error) => {
          this.loading = false
          const errorMessage = error ? error : 'Couldn’t subscribe to support plan.'
          this.$store.dispatch('app/displayError', errorMessage)
          this.$emit('close')
        })
    },

    goToBilling(ev) {
      ev.preventDefault()
      this.$router.push({path: '/settings/billing'})
      this.$emit('close')
    },
  },
}
</script>
