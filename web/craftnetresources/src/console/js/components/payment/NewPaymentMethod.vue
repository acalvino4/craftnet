<template>
  <div class="max-w-md">
    <RadioGroup class="space-y-4" v-model="selectedPaymentSourceValue">
      <!-- Personal + Org credit cards -->
      <template v-for="paymentSource in paymentSources">
        <RadioGroupOption
          class="ring-0 group"
          :value="(paymentSource.org ? 'org-' : '') + paymentSource.id"
          v-slot="{ active, checked }"
        >
          <payment-method-option
            :name="paymentSource.card.brand + ' ' + paymentSource.card.last4 + ' '"
            :description=" paymentSource.card.exp_month + '/' + paymentSource.card.exp_year"
            :info="paymentSource.org"
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
        <h2>Credit Card</h2>
        Enter your new credit card information:
        <div class="mt-2">
          <card-element
            v-if="!cardToken"
            ref="newCard"
          />
        </div>
        <h2>Billing address</h2>
        <address-fields
          v-model:address="billingAddress"
        />
      </div>

      <checkbox
        class="mt-6"
        id="replaceCard"
        label="Save as your primary billing information"
        :value="replaceCard"
        @input="$emit('update:replaceCard', !replaceCard)" />
    </template>


    <template v-if="selectedPaymentSource">
      <div class="mt-6 border p-4 rounded-md">
        <h2>Billing Address</h2>

        <template v-if="selectedPaymentSource.org">
          [show billing address]
          #{{selectedPaymentSource.org.billingAddressId}}
        </template>
        <template v-else>
          <div>[show billing address]</div>
          <div>[edit button]</div>
        </template>
      </div>
    </template>

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
      <template v-if="!selectedPaymentSource || selectedPaymentSource.canPurchase">
        <btn kind="primary" large @click="pay">Pay $XX</btn>
      </template>
      <template v-else>
        <btn kind="primary" large @click="pay">Submit for approval $XX</btn>
      </template>
    </div>
  </div>
</template>

<script>
import {
  RadioGroup, RadioGroupOption,
} from '@headlessui/vue'
import PaymentMethodOption from './PaymentMethodOption';
import CardElement from '../card/CardElement';
import AddressFields from '../billing/addresses/AddressFields';
import {mapState} from 'vuex';

export default {
  components: {
    RadioGroup, RadioGroupOption,
    PaymentMethodOption, CardElement,
    AddressFields,
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

      billingAddress: {

      },

      errors: {}
    }
  },

  computed: {
    ...mapState({
      cart: state => state.cart.cart,
      paymentSources: state => state.stripe.paymentSources,
      user: state => state.account.user,
    }),

    selectedPaymentSource() {
      return this.paymentSources.find(paymentSource => {
        if (paymentSource.id === parseInt(this.selectedPaymentSourceValue)) {
          return true
        }

        return !!(paymentSource.org && ('org-' + paymentSource.id) === this.selectedPaymentSourceValue);
      })
    },
  },

  methods: {
    pay() {
      console.log('pay', this.selectedPaymentSource)
      console.log('- selected payment source', this.selectedPaymentSource)
      console.log('- email', this.user.email)

      let checkoutData = {
        orderNumber: this.cart.number,
        token: this.selectedPaymentSource.token,
        expectedPrice: this.cart.totalPrice,
        // makePrimary: this.replaceCard,
      }

      console.log('- checkoutData', checkoutData)

      this.saveBillingInfos()
        .then(() => {
          this.$store.dispatch('cart/checkout', checkoutData)
            .then(() => {
              // this.$store.dispatch('cart/resetCart')
              this.$store.dispatch('app/displayError', 'Payment success.')
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
      let cartData = {
        billingAddress: {
          firstName: 'John',
          lastName: 'Smith',
          countryCode: 'FR',

          // firstName: this.billingInfo.firstName,
          // lastName: this.billingInfo.lastName,
          // businessName: this.billingInfo.businessName,
          // businessTaxId: this.billingInfo.businessTaxId,
          // address1: this.billingInfo.address1,
          // address2: this.billingInfo.address2,
          // country: this.billingInfo.country,
          // state: this.billingInfo.state,
          // city: this.billingInfo.city,
          // zipCode: this.billingInfo.zipCode,
        },
      }

      if (this.user) {
        cartData.email = this.user.email
      }

      return this.$store.dispatch('cart/saveCart', cartData)
    },
  },

  mounted() {
    this.$store.dispatch('stripe/getPaymentSources')
      .catch(() => {
        this.$store.dispatch('app/displayNotice', 'Couldn’t get payment sources.')
      })

  }
}

</script>

