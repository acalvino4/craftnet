/* global Craft */

import axios from 'axios'
import qs from 'qs'

export default {
  getApps() {
    return axios.get(Craft.actionUrl + '/craftnet/id/apps/get-apps')
  },

  disconnect(appHandle) {
    const data = {
      appTypeHandle: appHandle
    }

    return axios.post(Craft.actionUrl + '/craftnet/id/apps/disconnect', qs.stringify(data), {
      headers: {
        'X-CSRF-Token': Craft.csrfTokenValue,
      }
    })
  },
}
