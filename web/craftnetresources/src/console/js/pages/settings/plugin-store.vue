<template>
  <div>
    <page-header>
      <h1>Plugin Store</h1>
    </page-header>

    <div class="space-y-6">
      <template v-if="!showOld">
        <template v-if="!githubConnected">
          <div class="max-w-screen-sm mt-24 mx-auto">
            <div class="">
              <div class="flex">
                <div class="mt-2 mr-6 px-4">
                  <icon
                    class="text-blue-500 w-16 h-16"
                    icon="plug" />
                </div>

                <div class="flex-1">
                  <h3>Submit your plugins</h3>
                  <p>You can start submitting plugins to the Plugin Store once you’ve connected your GitHub account.</p>

                  <div class="mt-4">
                    <btn kind="primary" @click="githubConnected = true">Connect to GitHub</btn>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </template>

        <template v-else>
          <pane>
            <template v-slot:header>
              <h2>Payouts</h2>
            </template>

            <div class="flex">
              <div class="mr-6 px-4">
                <img
                  class="mt-2 w-16"
                  :src="staticImageUrl('stripe.svg')"
                />
              </div>

              <div class="flex-1">
                <h3>Stripe</h3>
                <div class="text-gray-500">
                  Automatic Stripe payouts only occur for U.S., European, Australian, and New Zealand based accounts.
                </div>

                <div class="mt-4">
                  <btn kind="primary">Connect</btn>
                </div>
              </div>
            </div>

            <hr>

            <div class="flex">
              <div class="mr-6 px-4">
                <img
                  class="mt-2 w-16"
                  :src="staticImageUrl('paypal.svg')"
                />
              </div>

              <div class="flex-1">
                <h3>Payouts Fallback</h3>
                <div class="text-gray-500">
                  Provide your PayPal email, which will be used for Plugin Store payouts in the event that there was a problem transferring via Stripe.
                </div>

                <paypal-payout class="mt-4" />
              </div>
            </div>
          </pane>
          <pane>
            <template v-slot:header>
              <h2>Payouts</h2>
            </template>

            <div>
              <field
                :first="true"
                :vertical="true"
                label="Where is your business located?"
              >
                <dropdown
                  v-model="selectedPayoutRegionHandle"
                  :options="payoutRegionOptions" />
              </field>
            </div>

            <template v-if="selectedPayoutRegion">
              <hr>
              
              <template v-if="selectedPayoutRegion.method === 'stripe'">

                <div class="flex">
                  <div class="mr-6 px-4">
                    <img
                      class="mt-2 w-16"
                      :src="staticImageUrl('stripe.svg')"
                    />
                  </div>

                  <div class="flex-1">
                    <h3>Stripe</h3>
                    <div class="text-gray-500">
                      Automatic Stripe payouts only occur for U.S., European, Australian, and New Zealand based accounts.
                    </div>

                    <div class="mt-4">
                      <btn kind="primary">Connect</btn>
                    </div>
                  </div>
                </div>
              </template>
              <template v-else>
                <div class="flex">
                  <div class="mr-6 px-4">
                    <img
                      class="mt-2 w-16"
                      :src="staticImageUrl('paypal.svg')"
                    />
                  </div>

                  <div class="flex-1">
                    <h3>PayPal</h3>
                    <div class="text-gray-500">
                      Provide your PayPal email, which will be used for Plugin Store payouts in the event that there was a problem transferring via Stripe.
                    </div>

                    <paypal-payout class="mt-4" />
                  </div>
                </div>
              </template>
            </template>
          </pane>

          <pane class="border border-red-500 mb-3">
            <template v-slot:header>
              <h2 class="mb-0 text-red-600">
                Danger Zone
              </h2>
            </template>

            <div class="flex items-center">
              <div class="mr-6 px-4">
                <img
                  class="w-16"
                  :src="staticImageUrl('github.svg')"
                />
              </div>

              <div class="flex-1">
                <h3>GitHub Account</h3>
                <p>You are connected to GitHub with the [Account Name] account.</p>

                <div class="mt-4">
                  <btn kind="danger" @click="githubConnected = false">Disconnect</btn>
                </div>
              </div>
            </div>
          </pane>
        </template>
      </template>

      <template v-else>
        <div class="space-y-6">
          <developer-preferences></developer-preferences>

          <pane>
            <template v-slot:header>
              <h2>Connected Apps</h2>
            </template>
            <connected-apps
              title="Connected Apps"
              :show-stripe="true"></connected-apps>
          </pane>
        </div>

        <pane>
          <div class="card-body">
            <form @submit.prevent="generateToken()">
              <h2>API Token</h2>

              <p v-if="notice">This is your new API token, <strong>keep
                it someplace safe</strong>.</p>

              <div class="mt-2 max-w-sm">
                <textbox
                  id="apiToken"
                  ref="apiTokenField"
                  class="mono"
                  :spellcheck="false"
                  v-model="apiToken"
                  :readonly="true" />
              </div>

              <btn
                class="mt-4"
                kind="primary"
                type="submit"
                :disabled="loading"
                :loading="loading">
                <template v-if="apiToken">Generate new API Token
                </template>
                <template v-else>Generate API Token</template>
              </btn>
            </form>
          </div>
        </pane>

        <pane>
          <template v-slot:header>
            <h2>Paypal Payouts</h2>
            <div class="text-gray-500">
              Provide your PayPal email, which will be used for Plugin Store payouts in the event that there was a problem transferring via Stripe.
            </div>
          </template>

          <paypal-payout />
        </pane>
      </template>
    </div>
  </div>
</template>

<script>
import {mapState} from 'vuex'
import PaypalPayout from '../../components/developer/PaypalPayout'
import DeveloperPreferences from '../../components/developer/Preferences'
import ConnectedApps from '../../components/developer/connected-apps/ConnectedApps'
import PageHeader from '@/console/js/components/PageHeader'
import helpers from '../../mixins/helpers';

export default {
  mixins: [helpers],

  data() {
    return {
      apiToken: '',
      loading: false,
      notice: false,
      showOld: true,
      githubConnected: false,
      selectedPayoutRegionHandle: '',
      payoutRegions: [
        {name: 'United States', handle: 'us', method: 'stripe'},
        {name: 'Europe', handle: 'eu', method: 'stripe'},
        {name: 'Australia', handle: 'au', method: 'stripe'},
        {name: 'New Zealand', handle: 'nz', method: 'stripe'},
        {name: 'Other', handle: 'other', method: 'paypal'},
      ],
    }
  },

  components: {
    PaypalPayout,
    DeveloperPreferences,
    ConnectedApps,
    PageHeader,
  },

  computed: {
    ...mapState({
      hasApiToken: state => state.account.hasApiToken,
      user: state => state.account.user,
    }),
    selectedPayoutRegion() {
      return this.payoutRegions.find(region => region.handle === this.selectedPayoutRegionHandle)
    },
    payoutRegionOptions() {
      return [
        {label: 'Select a region', value: ''},
        ...this.payoutRegions.map(region => {
          return {
            label: region.name,
            value: region.handle,
          }
        })
      ]
    },
  },

  methods: {
    generateToken() {
      this.loading = true

      this.$store.dispatch('account/generateApiToken')
        .then(response => {
          this.apiToken = response.data.apiToken

          const apiTokenInput = this.$refs.apiTokenField.$el.querySelector('input')

          this.$nextTick(() => {
            apiTokenInput.select()
          })

          this.notice = true
          this.loading = false
          this.$store.dispatch('app/displayNotice', 'API token generated.')
        })
        .catch(response => {
          this.loading = false
          const errorMessage = response.data && response.data.error ? response.data.error : 'Couldn’t generate API token.'
          this.$store.dispatch('app/displayError', errorMessage)
        })
    },
  },

  mounted() {
    if (this.hasApiToken) {
      this.apiToken = '****************************************'
    }
  }
}
</script>
