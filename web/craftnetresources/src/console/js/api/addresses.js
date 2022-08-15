/* global VUE_APP_URL_CONSOLE */

import axios from 'axios'

export default {
  getInfo({parents}) {
    return axios.post(VUE_APP_URL_CONSOLE + '/addresses/info',
      {
        parents,
      },
      {}
    )
  }
}