/* global Craft */

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

    return axios.post(Craft.actionUrl + '/craftnet/console/stripe/save-card', qs.stringify(data), {
      headers: {
        'X-CSRF-Token': Craft.csrfTokenValue,
      }
    })
  },

  removeCard() {
    return axios.post(Craft.actionUrl + '/craftnet/console/stripe/remove-card', {}, {
      headers: {
        'X-CSRF-Token': Craft.csrfTokenValue,
      }
    })
  },
}