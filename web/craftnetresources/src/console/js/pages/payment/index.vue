<template>
  <div>
    <page-header>
      <div class="flex-1 flex">
        <div class="flex-1">
          <h1 class="text-3xl">How would you like to pay?</h1>
        </div>

        <div class="space-x-4">
          <router-link to="/payment/old">Old</router-link>
        </div>
      </div>
    </page-header>

    <div class="max-w-md">
      <h2>Credit Card</h2>
      <RadioGroup class="mt-4 space-y-4" v-model="selectedPaymentSourceValue">
        <template v-for="paymentSource in paymentMethodsCheckout">
          <RadioGroupOption
            class="ring-0 group"
            :value="(paymentSource.org ? 'org-' : '') + paymentSource.id"
            v-slot="{ active, checked }"
          >
            <payment-method-option
              :name="paymentSource.card.brand + ' ' + paymentSource.card.last4 + ' '"
              :description=" paymentSource.card.exp_month + '/' + paymentSource.card.exp_year"
              :info="(paymentSource.org ? paymentSource.org.name : null)"
              :value="(paymentSource.org ? 'org-' : '') + paymentSource.id"
              :credit-card="paymentSource"
              :active="active"
              :checked="checked"
            />
          </RadioGroupOption>
        </template>

        <!-- New Credit Card -->
        <RadioGroupOption
          class="ring-0 group"
          :value="null"
          v-slot="{ active, checked }"
        >
          <payment-method-option
            name="New Credit Card"
            description="Visa, Mastercard, AMEX"
            :active="active"
            :checked="checked"
          />
        </RadioGroupOption>
      </RadioGroup>

      <template v-if="!selectedPaymentSourceValue">
        <div class="mt-6">
          <h3>Add a new credit card</h3>
          Enter your new credit card information:
          <div class="mt-2">
            <card-element
              v-if="!cardToken"
              ref="newCard"
            />
          </div>
        </div>
      </template>

      <template v-if="!selectedPaymentSource || !selectedPaymentSource.org">
        <div class="mt-8">
          <h2>Billing Address</h2>

          <div class="mt-4 space-y-4">
            <template v-for="address in addresses">
              <div class="flex border-b py-4">
                <div class="mt-1 mr-2">
                  <input
                    :id="'address-' + address.id"
                    type="radio"
                    :value="address.id"
                    v-model="billingAddressId"
                  />
                </div>
                <label class="flex flex-1" :for="'address-' + address.id">
                  <div class="flex-1">
                    <div class="flex items-center space-x-2">
                      <div v-if="address.countryCode">{{address.countryCode}}</div>
                      <div v-if="address.addressLine1">{{address.addressLine1}}</div>
                      <div v-if="address.locality">{{address.locality}}</div>
                      <div v-if="address.postalCode">{{address.postalCode}}</div>
                    </div>
                    <div class="text-sm font-mono text-gray-500">#{{address.id}}</div>
                  </div>
                  <div>
                    <a href="#">Edit</a>
                  </div>
                </label>
              </div>
            </template>
            <div>
              <a href="#">+ Add a new billing address</a>
            </div>
          </div>
        </div>
      </template>

      <h2 class="mt-8">More</h2>
      <checkbox
        class="mt-6"
        id="replaceCard"
        label="Save as your primary billing information"
        v-model="replaceCard"
      />

      <field
        :vertical="true"
        label-for="note"
        label="Note"
      >
        <textbox
          type="textarea"
          id="note"
        />
      </field>

      <div class="mt-6 space-x-2">
        <template v-if="!selectedPaymentSource || !selectedPaymentSource.org || selectedPaymentSource.org.canPurchase">
          <btn kind="primary" large @click="pay">Pay $XX</btn>
        </template>
        <template v-else>
          <btn kind="primary" large @click="requestApproval">Submit for approval $XX</btn>
        </template>
      </div>

      <div class="mt-16 border rounded-md p-4">
        <div>
          <h3>Cart Data</h3>
          <pre>{{cartData}}</pre>
        </div>
        <hr>
        <div>
          <h3>Pay Data</h3>
          <pre>{{payData}}</pre>
        </div>
      </div>

    </div>
  </div>
</template>
<script>
import {
  RadioGroup, RadioGroupOption,
} from '@headlessui/vue'
import PaymentMethodOption from '../../components/payment/PaymentMethodOption';
import CardElement from '../../components/card/CardElement';
import PageHeader from '../../components/PageHeader';
import {mapState} from 'vuex';

export default {
  components: {
    RadioGroup, RadioGroupOption,
    PaymentMethodOption,
    CardElement,
    PageHeader
  },

  data() {
    return {
      selectedPaymentSourceValue: null,
      billingInfo: {
        firstName: '',
        lastName: '',
        businessName: '',
        businessTaxId: '',
        address1: '',
        address2: '',
        country: '',
        state: '',
        city: '',
        zipCode: '',
      },
      billingAddress: {},
      errors: {},
      replaceCard: false,
      cardToken: null,
      billingAddressId: null,
    }
  },

  watch: {
    selectedPaymentSource() {
      this.billingAddressId = this.selectedPaymentSource && this.selectedPaymentSource.org ? this.selectedPaymentSource.org.billingAddressId : null;
    }
  },

  computed: {
    ...mapState({
      cart: state => state.cart.cart,
      paymentMethodsCheckout: state => state.paymentMethods.paymentMethodsCheckout,
      user: state => state.account.user,
      addresses: state => state.addresses.addresses,
    }),

    selectedPaymentSource() {
      if (!this.paymentMethodsCheckout.length) {
        return null;
      }

      return this.paymentMethodsCheckout.find(paymentSource => {
        if (paymentSource.id === parseInt(this.selectedPaymentSourceValue)) {
          return true
        }

        return !!(paymentSource.org && ('org-' + paymentSource.id) === this.selectedPaymentSourceValue);
      })
    },

    cartData() {
      let cartData = {}

      if (this.billingAddressId) {
        cartData.billingAddressId = parseInt(this.billingAddressId)
      }

      if (this.selectedPaymentSource) {
        if (this.selectedPaymentSource.org) {
          cartData.orgId = this.selectedPaymentSource.org.id
        } else {
          cartData.paymentSourceId = this.selectedPaymentSource.id;
        }
      }

      if (this.user) {
        cartData.email = this.user.email
      }

      return cartData;
    },

    payData() {
      return {
        orderNumber: this.cart.number,
        token: this.selectedPaymentSource ? this.selectedPaymentSource.token : null,
        expectedPrice: this.cart.totalPrice,
        // makePrimary: this.replaceCard,
      }
    },

    addressOptions() {
      const options = [
        {
          label: 'Select an address',
          value: '',
        },
      ]

      if (this.addresses) {
        this.addresses.forEach(address => {
          options.push({
            label: '#' + address.id,
            value: address.id,
          })
        })
      }

      return options
    },

    addressRadioOptions() {
      const options = []

      if (this.addresses) {
        this.addresses.forEach(address => {
          options.push({
            label: '#' + address.id,
            value: address.id,
          })
        })
      }

      return options
    },
  },


  methods: {
    pay() {
      this.saveBillingInfos()
        .then(() => {
          this.$store.dispatch('cart/checkout', this.payData)
            .then(() => {
              this.$store.dispatch('cart/resetCart')
              this.$store.dispatch('app/displayNotice', 'Payment success.')
            })
            .catch(() => {
              this.$store.dispatch('app/displayError', 'There was an error processing your payment.')
            })
        })
        .catch(() => {
          this.$store.dispatch('app/displayError', 'Couldn’t save billing information.')
        })
    },

    saveBillingInfos() {
      return this.$store.dispatch('cart/saveCart', this.cartData)
    },

    requestApproval() {
      console.log('this.cart', this.cart.number)
      this.$store.dispatch('organizations/requestOrderApproval', {
          organizationId: this.selectedPaymentSource.org.id,
          orderNumber: this.cart.number,
        })
        .then(() => {
          this.$store.dispatch('app/displayNotice', 'Approval requested.')
        })
        .catch(() => {
          this.$store.dispatch('app/displayError', 'Couldn’t request approval.')
        })
    }
  },

  mounted() {
    this.$store.dispatch('paymentMethods/getPaymentMethodsCheckout')
      .catch(() => {
        this.$store.dispatch('app/displayError', 'Couldn’t get payment methods.')
      })

    this.$store.dispatch('cart/getCart')

    this.$store.dispatch('addresses/getAddresses')
  }
}
</script>