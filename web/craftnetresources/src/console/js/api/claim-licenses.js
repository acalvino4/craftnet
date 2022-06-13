/* global Craft */

import axios from 'axios'
import qs from 'qs'

export default {
  claimLicensesByEmail(email) {
    const data = {
      email: email,
    }

    return axios.post(Craft.actionUrl + '/craftnet/console/claim-licenses', qs.stringify(data), {
      headers: {
        'X-CSRF-Token': Craft.csrfTokenValue,
      }
    })
  },
}