import Vue from 'vue'
import Vuex from 'vuex'
import craftIdApi from '../../api/craft-id'

Vue.use(Vuex)

/**
 * State
 */
const state = {
  countries: null,
}

/**
 * Getters
 */
const getters = {
  countryOptions(state) {
    if (!state.countries) {
      return []
    }

    let options = []

    for (let iso in state.countries) {
      if (state.countries.hasOwnProperty(iso)) {
        options.push({
          label: state.countries[iso].name,
          value: iso,
        })
      }
    }

    return options
  },

  stateOptions(state) {
    return iso => {
      if (!state.countries) {
        return []
      }

      let options = []

      if (!state.countries[iso] || (state.countries[iso] && !state.countries[iso].states)) {
        return []
      }

      const states = state.countries[iso].states

      for (let stateIso in states) {
        if (states.hasOwnProperty(stateIso)) {
          options.push({
            label: states[stateIso],
            value: stateIso,
          })
        }
      }

      return options
    }
  },
}

/**
 * Actions
 */
const actions = {
  getCountries({commit, state}) {
    return new Promise((resolve, reject) => {
      if (state.countries) {
        resolve()
        return
      }

      craftIdApi.getCountries()
        .then((response) => {
          commit('updateCountries', {countries: response.data})
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
  updateCountries(state, {countries}) {
    state.countries = countries
  },
}

export default {
  namespaced: true,
  state,
  getters,
  actions,
  mutations
}
