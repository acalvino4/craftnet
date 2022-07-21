<template>
  <div class="max-w-md">
    <RadioGroup class="space-y-4" v-model="selectedCreditCardValue">
      <template v-for="creditCard in creditCards">
        <RadioGroupOption
          class="ring-0 group"
          :value="creditCard.id"
          v-slot="{ active, checked }"
        >
          <payment-method-option
            :name="creditCard.brand + ' ' + creditCard.last4"
            :description=" creditCard.exp_month + '/' + creditCard.exp_year"
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
        <billing-info-form
          v-model:billingInfo="billingInfo"
          :errors="errors"
          :vertical="true"
        />
      </div>

      <checkbox
        class="mt-6"
        id="replaceCard"
        label="Save as primary billing information"
        :value="replaceCard"
        @input="$emit('update:replaceCard', !replaceCard)" />
    </template>

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

<script setup>
import { ref, computed } from 'vue'
import {
  RadioGroup, RadioGroupOption,
} from '@headlessui/vue'
import PaymentMethodOption from './PaymentMethodOption';
import CardElement from '../card/CardElement';
import BillingInfoForm from './BillingInfo';

const selectedCreditCardValue = ref(null)
const creditCards = ref([
  {
    id: 1,
    brand: 'Visa',
    last4: '4242',
    exp_month: '01',
    exp_year: '28',
  },
  {
    id: 2,
    brand: 'Visa',
    last4: '4545',
    exp_month: '05',
    exp_year: '25',
    org: 'Pixel & Tonic',
  },
])

const selectedCreditCard = computed(() => creditCards.value.find(creditCard => creditCard.id === selectedCreditCardValue.value))

const billingInfo = ref({
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
});

const errors = ref({})

</script>