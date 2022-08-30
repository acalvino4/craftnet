/* global Craft */

import axios from './axios'

export default {
  getAccount() {
    return axios.get(Craft.actionUrl + '/craftnet/console/stripe/account')
  },

  disconnect() {
    return axios.post(Craft.actionUrl + '/craftnet/console/stripe/disconnect')
  },
}