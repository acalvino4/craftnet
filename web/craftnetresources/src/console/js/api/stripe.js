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

  saveCard(source) {
    const data = {
      paymentMethodId: source.id
    }

    return axios.post(VUE_APP_URL_CONSOLE + '/cards', qs.stringify(data), {
      headers: {
        'X-CSRF-Token': Craft.csrfTokenValue,
      }
    })
  },

  removeCard(cardId) {
    return axios.delete(VUE_APP_URL_CONSOLE + '/cards/' + cardId)
  },

  getCards() {
    return axios.get(VUE_APP_URL_CONSOLE + '/cards')
  }
}