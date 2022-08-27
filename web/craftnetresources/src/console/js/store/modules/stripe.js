import stripeApi from '../../api/stripe'

/**
 * State
 */
const state = {
  stripeAccount: null,
}

/**
 * Getters
 */
const getters = {}

/**
 * Actions
 */
const actions = {
  disconnectStripeAccount({commit}) {
    return new Promise((resolve, reject) => {
      stripeApi.disconnect()
        .then((response) => {
          commit('disconnectStripeAccount')
          resolve(response)
        })
        .catch((response) => {
          reject(response)
        })
    })
  },

  getStripeAccount({commit}) {
    return new Promise((resolve, reject) => {
      stripeApi.getAccount()
        .then((response) => {
          commit('updateStripeAccount', {response})
          resolve(response)
        })
        .catch((response) => {
          reject(response)
        })
    })
  },
}

/**
 * Mutations
 */
const mutations = {
  disconnectStripeAccount(state) {
    state.stripeAccount = null
  },

  updateStripeAccount(state, {response}) {
    state.stripeAccount = response.data
  },
}

export default {
  namespaced: true,
  state,
  getters,
  actions,
  mutations
}
