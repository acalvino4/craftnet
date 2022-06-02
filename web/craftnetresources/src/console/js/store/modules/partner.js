import * as types from '../mutation-types'
import partnerApi from '../../api/partners'

/**
 * State
 */
const state = {
    partner: null
}

/**
 * Getters
 */
const getters = {
    showPartnerFeatures(state) {
        return state.enablePartnerFeatures
    }
}

/**
 * Actions
 */
const actions = {
    initPartner({commit, state}) {
        return new Promise((resolve, reject) => {
            if (state.partner) {
                resolve({data: {partner: state.partner}})
            } else {
                partnerApi.getPartner()
                    .then((response) => {
                        if (response.data && !response.data.error) {
                            commit(types.RECEIVE_PARTNER, response.data.partner)
                            resolve(response)
                        } else {
                            reject(response)
                        }
                    })
                    .catch((error) => {
                        // @todo return error responses like the rest of these actions
                        reject(error.response)
                    })
            }
        })
    },

    patchPartner({commit, state}, {draft, files}) {
        return new Promise((resolve, reject) => {
            partnerApi.patchPartner(draft, files, state.partner.id)
                .then((response) => {
                    if (response.data.success) {
                        commit(types.RECEIVE_PARTNER, response.data.partner)
                    }
                    resolve(response)
                })
                .catch((error) => {
                    // @todo test these error messages
                    if (error.response.data) {
                        reject(error.response.data.error)
                    }

                    reject(error.response.statusText)
                })
        })
    },

    patchPartnerContact({commit, state}, {draft, files}) {
        return new Promise((resolve, reject) => {
            partnerApi.patchPartnerContact(draft, files, state.partner.id)
                .then((response) => {
                    if (response.data.success) {
                        commit(types.RECEIVE_PARTNER, response.data.partner)
                    }
                    resolve(response)
                })
                .catch((error) => {
                    // @todo test these error messages
                    if (error.response.data) {
                        reject(error.response.data.error)
                    }

                    reject(error.response.statusText)
                })
        })
    },

    patchPartnerLocations({commit, state}, locations) {
        return new Promise((resolve, reject) => {
            partnerApi.patchPartnerLocations(locations, state.partner.id)
                .then((response) => {
                    if (response.data.success) {
                        commit(types.RECEIVE_PARTNER_LOCATIONS, response.data.partner.locations)
                    }
                    resolve(response)
                })
                .catch((error) => {
                    reject(error.response.statusText)
                })
        })
    },

    patchPartnerProjects({commit, state}, projects) {
        return new Promise((resolve, reject) => {
            partnerApi.patchPartnerProjects(projects, state.partner.id)
                .then((response) => {
                    if (response.data.success) {
                        commit(types.RECEIVE_PARTNER_PROJECTS, response.data.partner.projects)
                    }
                    resolve(response)
                })
                .catch((error) => {
                    reject(error.response.statusText)
                })
        })
    },
}

/**
 * Mutations
 */
const mutations = {
    [types.RECEIVE_PARTNER](state, partner) {
        state.partner = partner
    },
    [types.RECEIVE_PARTNER_LOCATIONS](state, locations) {
        state.partner.locations = locations
    },
    [types.RECEIVE_PARTNER_PROJECTS](state, projects) {
        state.partner.projects = projects
    },
}

export default {
    state,
    getters,
    actions,
    mutations
}
