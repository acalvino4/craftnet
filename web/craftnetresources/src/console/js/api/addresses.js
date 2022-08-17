/* global VUE_APP_URL_CONSOLE */

import axios from 'axios'

export default {
  deleteAddress(addressId) {
    return axios.delete(VUE_APP_URL_CONSOLE + '/addresses/' + addressId)
  },

  getAddress(addressId) {
    return axios.get(VUE_APP_URL_CONSOLE + '/addresses')
  },

  getAddresses() {
    return axios.get(VUE_APP_URL_CONSOLE + '/addresses')
  },

  getCountries() {
    return axios.get(VUE_APP_URL_CONSOLE + '/addresses/countries')
  },

  getInfo({parents}) {
    return axios.post(VUE_APP_URL_CONSOLE + '/addresses/info',
      {
        parents,
      },
      {}
    )
  },
  saveAddress(address) {
    return axios.post(VUE_APP_URL_CONSOLE + '/addresses' + (address.id ? '/' + address.id : ''), address)
  },
}