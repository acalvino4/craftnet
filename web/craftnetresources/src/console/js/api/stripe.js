/* global Craft, VUE_APP_URL_CONSOLE */

import axios from 'axios'
import qs from 'qs'

export default {
  getAccount() {
    return axios.get(Craft.actionUrl + '/craftnet/console/stripe/account')
  },

  disconnect() {
    return axios.post(Craft.actionUrl + '/craftnet/console/stripe/disconnect', {}, {
      headers: {
        'X-CSRF-Token': Craft.csrfTokenValue,
      }
    })
  },

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

  saveCard({paymentSourceId, card}) {
    return axios.post(VUE_APP_URL_CONSOLE + '/cards/' + paymentSourceId, qs.stringify(card), {
      headers: {
        'X-CSRF-Token': Craft.csrfTokenValue,
      }
    })
  },

  removeCard(cardId) {
    return axios.delete(VUE_APP_URL_CONSOLE + '/cards/' + cardId)
  },

  getPaymentMethods() {
    return axios.get(VUE_APP_URL_CONSOLE + '/payment-methods')
  },

  getPaymentSources() {
    return axios.get(VUE_APP_URL_CONSOLE + '/cards/payment-sources')
  }
}