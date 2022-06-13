<template>
  <div>
    <template v-if="user">
      <template v-if="card">
        <p><label><input
          type="radio"
          value="existingCard"
          :checked="paymentMode === 'existingCard'"
          name="paymentMode"
          @input="$emit('update:paymentMode', $event.target.value)" /> Use card
          <span>{{ card.brand }} •••• •••• •••• {{
              card.last4
            }} — {{ card.exp_month }}/{{ card.exp_year }}</span></label></p>
      </template>

      <p><label><input
        type="radio"
        value="newCard"
        :checked="paymentMode === 'newCard'"
        name="paymentMode"
        @input="$emit('update:paymentMode', $event.target.value)" /> Use a new
        credit card</label></p>

      <template v-if="paymentMode === 'newCard'">
        <card-element
          v-if="!cardToken"
          ref="newCard" />
        <p v-else>{{ cardToken.card.brand }} •••• •••• ••••
          {{ cardToken.card.last4 }} ({{
            cardToken.card.exp_month
          }}/{{ cardToken.card.exp_year }}) <a
            class="delete icon"
            @click="cardToken = null">Delete</a></p>

        <checkbox
          id="replaceCard"
          label="Save as my new credit card"
          :value="replaceCard"
          @input="$emit('update:replaceCard', !replaceCard)" />
      </template>
    </template>

    <template v-else>
      <card-element ref="guestCard"></card-element>
    </template>
  </div>
</template>

<script>
import {mapState} from 'vuex'

import CardElement from '../card/CardElement'

export default {
  props: ['card', 'cardToken', 'paymentMode', 'replaceCard'],

  components: {
    CardElement,
  },

  computed: {
    ...mapState({
      user: state => state.account.user,
    }),
  }
}
</script>