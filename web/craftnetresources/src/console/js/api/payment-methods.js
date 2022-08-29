/* global Craft, VUE_APP_URL_CONSOLE */

import axios from 'axios'
import qs from 'qs'

export default {
  addPaymentMethod(source) {
    const data = {
      paymentMethodId: source.id
    }

    return axios.post(VUE_APP_URL_CONSOLE + '/payment-methods', qs.stringify(data), {
      headers: {
        'X-CSRF-Token': Craft.csrfTokenValue,
      }
    })
  },

  savePaymentMethod(data) {
    const paymentMethodId = data.paymentMethodId
    return axios.post(VUE_APP_URL_CONSOLE + '/payment-methods/' + paymentMethodId, qs.stringify(data), {
      headers: {
        'X-CSRF-Token': Craft.csrfTokenValue,
      }
    })
  },

  removePaymentMethod(paymentMethodId) {
    return axios.delete(VUE_APP_URL_CONSOLE + '/payment-methods/' + paymentMethodId)
  },

  getPaymentMethods() {
    return axios.get(VUE_APP_URL_CONSOLE + '/payment-methods')
  },

  getPaymentMethodsCheckout() {
    return axios.get(VUE_APP_URL_CONSOLE + '/payment-methods/checkout')
  }
}
