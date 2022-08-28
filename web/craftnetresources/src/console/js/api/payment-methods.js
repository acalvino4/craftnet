/* global Craft, VUE_APP_URL_CONSOLE */

import axios from 'axios'
import qs from 'qs'

export default {
  addCard(source) {
    const data = {
      paymentMethodId: source.id
    }

    return axios.post(VUE_APP_URL_CONSOLE + '/payment-methods', qs.stringify(data), {
      headers: {
        'X-CSRF-Token': Craft.csrfTokenValue,
      }
    })
  },

  savePaymentMethod({paymentMethodId, card}) {
    return axios.post(VUE_APP_URL_CONSOLE + '/payment-methods/' + paymentMethodId, qs.stringify(card), {
      headers: {
        'X-CSRF-Token': Craft.csrfTokenValue,
      }
    })
  },

  removeCard(cardId) {
    return axios.delete(VUE_APP_URL_CONSOLE + '/payment-methods/' + cardId)
  },

  getPaymentMethods() {
    return axios.get(VUE_APP_URL_CONSOLE + '/payment-methods')
  },

  getPaymentMethodsCheckout() {
    return axios.get(VUE_APP_URL_CONSOLE + '/payment-methods/checkout')
  }
}
