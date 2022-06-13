import Vue from 'vue'
import Vuex from 'vuex'
import cmsLicensesApi from '../../api/cms-licenses'

Vue.use(Vuex)

/**
 * State
 */
const state = {
  expiringCmsLicensesTotal: 0,
}

/**
 * Getters
 */
const getters = {}

/**
 * Actions
 */
const actions = {
  getExpiringCmsLicensesTotal({commit}) {
    return new Promise((resolve, reject) => {
      cmsLicensesApi.getExpiringCmsLicensesTotal()
        .then((response) => {
          if (typeof response.data !== 'undefined' && !response.data.error) {
            commit('updateExpiringCmsLicensesTotal', response.data)
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
  updateExpiringCmsLicensesTotal(state, total) {
    state.expiringCmsLicensesTotal = total
  },
}

export default {
  namespaced: true,
  state,
  getters,
  actions,
  mutations
}
