import Vue from 'vue'
import Vuex from 'vuex'
import pluginLicensesApi from '../../api/plugin-licenses'

Vue.use(Vuex)

/**
 * State
 */
const state = {
  expiringPluginLicensesTotal: 0,
}

/**
 * Getters
 */
const getters = {}

/**
 * Actions
 */
const actions = {
  getExpiringPluginLicensesTotal({commit}) {
    return new Promise((resolve, reject) => {
      pluginLicensesApi.getExpiringPluginLicensesTotal()
        .then((response) => {
          if (typeof response.data !== 'undefined' && !response.data.error) {
            commit('updateExpiringPluginLicensesTotal', response.data)
            resolve(response)
          } else {
            reject(response)
          }
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
  updateExpiringPluginLicensesTotal(state, total) {
    state.expiringPluginLicensesTotal = total
  }
}

export default {
  namespaced: true,
  state,
  getters,
  actions,
  mutations
}
