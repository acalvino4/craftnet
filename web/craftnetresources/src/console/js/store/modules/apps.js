import appsApi from '../../api/apps'

/**
 * State
 */
const state = {
  apps: {},
  appsLoading: false,
}

/**
 * Getters
 */
const getters = {}

/**
 * Actions
 */
const actions = {
  getApps({commit, state, dispatch}) {
    if (state.appsLoading) {
      return false
    }

    if (Object.keys(state.apps).length > 0) {
      return false
    }

    commit('updateAppsLoading', true)

    return new Promise((resolve, reject) => {
      appsApi.getApps()
        .then((response) => {
          dispatch('stripe/getStripeAccount', {}, {root: true})
            .then(() => {
              commit('updateAppsLoading', false)
              commit('updateApps', {apps: response.data})
              resolve()
            })
            .catch((error) => {
              commit('updateAppsLoading', false)
              reject(error.response)
            })
        })
        .catch((error) => {
          commit('updateAppsLoading', false)
          reject(error.response)
        })
    })
  },

  connectAppCallback({commit}, apps) {
    commit('updateApps', {apps})
  },

  disconnectApp({commit}, appHandle) {
    return new Promise((resolve, reject) => {
      appsApi.disconnect(appHandle)
        .then((response) => {
          commit('disconnectApp', {appHandle})
          resolve(response)
        })
        .catch((error) => {
          reject(error.response)
        })
    })
  },
}

/**
 * Mutations
 */
const mutations = {
  updateApps(state, {apps}) {
    state.apps = apps
  },

  updateAppsLoading(state, loading) {
    state.appsLoading = loading
  },

  disconnectApp(state, {appHandle}) {
    delete state.apps[appHandle]
  },
}

export default {
  namespaced: true,
  state,
  getters,
  actions,
  mutations
}
