/* global VUE_APP_CRAFT_API_ENDPOINT */

import axios from 'axios'

export default {
  getCoreData() {
    return axios.get(VUE_APP_CRAFT_API_ENDPOINT + '/plugin-store/core-data', {withCredentials: false})
  },

  getPlugins(pluginIds) {
    return axios.get(VUE_APP_CRAFT_API_ENDPOINT + '/plugins', {
      params: {
        ids: pluginIds.join(',')
      },
      withCredentials: false
    })
  }
}