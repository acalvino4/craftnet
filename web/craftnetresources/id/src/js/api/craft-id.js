/* global Craft */

import axios from 'axios'

export default {
  getCountries() {
    return axios.get(Craft.actionUrl + '/craftnet/id/craft-id/countries', {}, {
      headers: {
        'X-CSRF-Token': Craft.csrfTokenValue,
      }
    })
  }
}
