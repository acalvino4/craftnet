import addressesApi from '../../api/addresses';

/**
 * State
 */
const state = {
  addresses: null,
  countries: null,
  info: null,
}

/**
 * Getters
 */
const getters = {
}

/**
 * Actions
 */
const actions = {
  deleteAddress({dispatch}, addressId) {
    return new Promise((resolve, reject) => {
      addressesApi.deleteAddress(addressId)
        .then((deleteAddressResponse) => {
          dispatch('getAddresses')
            .then((getAddressesResponse) => {
              resolve({
                deleteAddressResponse,
                getAddressesResponse,
              })
            })
        })
        .catch((error) => {
          reject(error)
        })
    })
  },
  getAddress({commit}) {
    return new Promise((resolve, reject) => {
      addressesApi.getAddresses()
        .then((response) => {
          commit('updateAddresses', {addresses: response.data.addresses})
          resolve(response)
        })
        .catch((error) => {
          reject(error)
        })
    })
  },
  getAddresses({commit}) {
    return new Promise((resolve, reject) => {
      addressesApi.getAddresses()
        .then((response) => {
          commit('updateAddresses', {addresses: response.data.addresses})
          resolve(response)
        })
        .catch((error) => {
          reject(error)
        })
    })
  },
  getCountries({commit}) {
    return new Promise((resolve, reject) => {
      addressesApi.getCountries()
        .then((response) => {
          commit('updateCountries', {countries: response.data.countries})
          resolve(response)
        })
        .catch((error) => {
          reject(error)
        })
    })
  },
  getInfo({commit}, {parents}) {
    return new Promise((resolve, reject) => {
      addressesApi.getInfo({parents})
        .then((response) => {
          commit('updateInfo', {info: response.data.addressInfo})
          resolve(response)
        })
        .catch((error) => {
          reject(error)
        })
    })
  },
  saveAddress({dispatch}, address) {
    return new Promise((resolve, reject) => {
      addressesApi.saveAddress(address)
        .then((saveAddressResponse) => {
          dispatch('getAddresses')
            .then((getAddressesResponse) => {
              resolve({
                saveAddressResponse,
                getAddressesResponse,
              })
            })
        })
        .catch((error) => {
          reject(error)
        })
    })
  }
}

/**
 * Mutations
 */
const mutations = {
  updateAddresses(state, {addresses}) {
    state.addresses = addresses
  },
  updateCountries(state, {countries}) {
    state.countries = countries
  },
  updateInfo(state, {info}) {
    state.info = info
  },
}

export default {
  namespaced: true,
  state,
  getters,
  actions,
  mutations
}
