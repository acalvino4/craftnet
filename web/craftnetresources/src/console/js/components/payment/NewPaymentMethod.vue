<template>
  <div class="max-w-md">
    <RadioGroup class="space-y-4" v-model="selectedCreditCardValue">
      <!-- Personal + Org credit cards -->
      <template v-for="creditCard in creditCards">
        <RadioGroupOption
          class="ring-0 group"
          :value="creditCard.id"
          v-slot="{ active, checked }"
        >
          <payment-method-option
            :name="creditCard.card.brand + ' ' + creditCard.card.last4"
            :description=" creditCard.card.exp_month + '/' + creditCard.card.exp_year"
            :info="creditCard.org"
            :value="creditCard.id"
            :credit-card="creditCard"
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

    <template v-if="!selectedCreditCardValue">
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
      <template v-if="!selectedCreditCard || (selectedCreditCard && !selectedCreditCard.org)">
        <btn kind="primary" large>Pay $XX</btn>
      </template>
      <template v-else>
        <btn kind="primary" large>Submit for approval $XX</btn>
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
      selectedCreditCardValue: null,
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
      cards: state => state.stripe.cards,
    }),

    creditCards() {
      const cards = []

      // Personal card
      for (const cardKey in this.cards) {
        const card = this.cards[cardKey];

        if (card.isPrimary) {
          cards.push(card)
        }
      }

      // Org cards

      return cards
    },

    selectedCreditCard() {
      return this.creditCards.find(creditCard => creditCard.id === this.selectedCreditCardValue)
    },
  },

  mounted() {
    this.$store.dispatch('stripe/getCards')
      .then(() => {
      })
      .catch(() => {
        this.$store.dispatch('app/displayNotice', 'Couldn’t get credit cards.')
      })

  }
}

</script>

