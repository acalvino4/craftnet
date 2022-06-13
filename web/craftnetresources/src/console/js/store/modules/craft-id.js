import craftIdApi from '../../api/craft-id'

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

    const options = []

    for (const iso in state.countries) {
      if (Object.prototype.hasOwnProperty.call(state, iso)) {
        options.push({
          label: state.countries[iso].name,
          value: iso,
        })
      }
    }

    return options
  },

  stateOptions(state) {
    return (iso) => {
      if (!state.countries) {
        return []
      }

      const options = []

      if (!state.countries[iso] || (state.countries[iso] && !state.countries[iso].states)) {
        return []
      }

      const states = state.countries[iso].states

      for (const stateIso in states) {
        if (Object.prototype.hasOwnProperty.call(states, stateIso)) {
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
