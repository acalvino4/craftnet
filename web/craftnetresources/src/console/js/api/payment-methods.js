/* global Craft, VUE_APP_URL_CONSOLE */

import axios from 'axios'
import qs from 'qs'

export default {
  savePaymentMethod(data) {
    const id = data.id
    const isNew = !id

    return axios.post(VUE_APP_URL_CONSOLE + '/payment-methods' + (!isNew ? '/' + id : ''), qs.stringify(data), {
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
