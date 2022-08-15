import addressesApi from '../../api/addresses';

/**
 * State
 */
const state = {
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
  getInfo({commit}, {parents}) {
    return new Promise((resolve, reject) => {
      addressesApi.getInfo({parents})
        .then((response) => {
          commit('updateInfo', {info: response.data.addressInfo})
        })
        .catch((error) => {
          reject(error)
        })
    })
  },
}

/**
 * Mutations
 */
const mutations = {
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
