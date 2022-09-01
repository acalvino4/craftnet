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
      <h2>Payment Method</h2>
      <RadioGroup class="mt-4 space-y-4" v-model="selectedPaymentMethodValue">
        <template v-for="paymentMethod in paymentMethodsCheckout">
          <RadioGroupOption
            class="ring-0 group"
            :value="(paymentMethod.org ? 'org-' : '') + paymentMethod.id"
            v-slot="{ active, checked }"
          >
            <payment-method-option
              :name="paymentMethod.card.brand + ' ' + paymentMethod.card.last4 + ' '"
              :description=" paymentMethod.card.exp_month + '/' + paymentMethod.card.exp_year"
              :info="(paymentMethod.org ? paymentMethod.org.name : null)"
              :value="(paymentMethod.org ? 'org-' : '') + paymentMethod.id"
              :credit-card="paymentMethod"
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
            name="New Payment Method"
            description="Add a new payment method"
            :active="active"
            :checked="checked"
          />
        </RadioGroupOption>
      </RadioGroup>

      <template v-if="!selectedPaymentMethodValue">
        <div class="mt-6">
          <h3>Credit Card</h3>
          Enter your new credit card information:
          <div class="mt-2">
            <card-element
              v-if="!cardToken"
              ref="newCard"
            />
          </div>
        </div>
      </template>

      <template v-if="!selectedPaymentMethod">
        <div class="mt-8">
          <h3>Billing Address</h3>

          <address-fields
            v-model:address="billingAddress"
          />
        </div>
      </template>

      <checkbox
        class="mt-6"
        id="savePaymentMethod"
        label="Save payment method"
        v-model="savePaymentMethod"
      />

      <template v-if="savePaymentMethod">
        <checkbox
          class="mt-6"
          id="replaceCard"
          label="Save as your primary payment method"
          v-model="replaceCard"
        />
      </template>

      <h2 class="mt-8">More</h2>

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
        <template v-if="!selectedPaymentMethod || !selectedPaymentMethod.org || selectedPaymentMethod.org.canPurchase">
          <btn
            :disabled="checkoutLoading"
            :loading="checkoutLoading"
            kind="primary"
            large
            @click="pay"
          >Pay ${{ cart.totalPrice }}</btn>
        </template>
        <template v-else>
          <btn
            :disabled="checkoutLoading"
            :loading="checkoutLoading"
            kind="primary"
            large
            @click="requestApproval"
          >Submit for approval ${{ cart.totalPrice }}</btn>
        </template>
      </div>

      <div class="mt-16 border rounded-md p-4">
        <div>
          <h3>Update cart request data</h3>
          <pre>{{cartData}}</pre>
        </div>
        <hr>
        <div>
          <h3>Payment request data</h3>
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
import AddressFields from '../../components/billing/AddressFields';

export default {
  components: {
    AddressFields,
    RadioGroup, RadioGroupOption,
    PaymentMethodOption,
    CardElement,
    PageHeader
  },

  data() {
    return {
      selectedPaymentMethodValue: null,
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
      savePaymentMethod: false,
      cardToken: null,
      checkoutLoading: false,
    }
  },

  computed: {
    ...mapState({
      cart: state => state.cart.cart,
      paymentMethodsCheckout: state => state.paymentMethods.paymentMethodsCheckout,
      user: state => state.account.user,
    }),

    selectedPaymentMethod() {
      if (!this.paymentMethodsCheckout.length) {
        return null;
      }

      return this.paymentMethodsCheckout.find(paymentMethod => {
        if (paymentMethod.id === parseInt(this.selectedPaymentMethodValue)) {
          return true
        }

        return !!(paymentMethod.org && ('org-' + paymentMethod.id) === this.selectedPaymentMethodValue);
      })
    },

    cartData() {
      let cartData = {}

      if (this.selectedPaymentMethod) {
        if (this.selectedPaymentMethod.org) {
          cartData.orgId = this.selectedPaymentMethod.org.id
        } else {
          cartData.paymentSourceId = this.selectedPaymentMethod.id;
        }

        cartData.billingAddress = this.selectedPaymentMethod.billingAddress
      } else {
        cartData.billingAddress = this.billingAddress
      }

      if (this.user) {
        cartData.email = this.user.email
      }

      return cartData;
    },

    payData() {
      return {
        orderNumber: (this.cart ? this.cart.number : null),
        token: this.selectedPaymentMethod ? this.selectedPaymentMethod.token : null,
        expectedPrice: (this.cart ? this.cart.totalPrice : null),
        // makePrimary: this.replaceCard,
      }
    },
  },


  methods: {
    pay() {
      this.checkoutLoading = true
      this.saveBillingInfos()
        .then(() => {
          this.$store.dispatch('cart/checkout', this.payData)
            .then(() => {
              this.$store.dispatch('cart/resetCart')
              this.$router.push({path: '/thank-you'})
              this.checkoutLoading = false
            })
            .catch(() => {
              this.$store.dispatch('app/displayError', 'There was an error processing your payment.')
              this.checkoutLoading = false
            })
        })
        .catch((error) => {
          this.$store.dispatch('app/displayError', error.response.data && error.response.data.message ? error.response.data.message : 'Couldn’t save billing information.')
          this.checkoutLoading = false
        })
    },

    saveBillingInfos() {
      return this.$store.dispatch('cart/saveCart', this.cartData)
    },

    requestApproval() {
      this.checkoutLoading = true
      this.$store.dispatch('organizations/requestOrderApproval', {
          organizationId: this.selectedPaymentMethod.org.id,
          orderNumber: this.cart.number,
        })
        .then(() => {
          this.$store.dispatch('cart/resetCart')
          this.$router.push({path: '/approval-requested'})
          this.checkoutLoading = false
        })
        .catch(() => {
          this.$store.dispatch('app/displayError', 'Couldn’t request approval.')
          this.checkoutLoading = false
        })
    }
  },

  mounted() {
    this.$store.dispatch('paymentMethods/getPaymentMethodsCheckout')
      .catch(() => {
        this.$store.dispatch('app/displayError', 'Couldn’t get payment methods.')
      })

    this.$store.dispatch('cart/getCart')
  }
}
</script>