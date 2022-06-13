import developerSupportApi from '../../api/developer-support'

/**
 * State
 */
const state = {
  totalSeats: 5,
  subscriptionInfo: null,
  members: [
    {name: "Brandon", email: "brandon@craftcms.com", avatar: "brandon.png", role: "owner"},
    {name: "Andris", email: "andris@craftcms.com", avatar: "andris.png", role: "member"},
    {name: "Ben", email: "ben@craftcms.com", avatar: "ben.png", role: "member"},
  ],
  plans: [
    {
      icon: 'mail',
      handle: "basic",
      name: "Basic",
      price: 0,
      features: [
        'Basic developer-to-developer support via email (no guaranteed response time)',
      ]
    },
    {
      icon: 'support',
      handle: "pro",
      name: "Pro",
      price: 75,
      features: [
        'Developer-to-developer support via email',
        'Guaranteed 12 hour or less time to first response on weekdays',
      ]
    },
    {
      icon: 'lightning-bolt',
      handle: "premium",
      name: "Premium",
      price: 750,
      features: [
        'Prioritized developer-to-developer support via email',
        'Guaranteed 2 hour or less time to first response on weekdays',
        'Guaranteed 12 hour or less time to first response on weekends',
      ]
    },
  ]
}

/**
 * Getters
 */
const getters = {
  currentPlan(state, getters) {
    return state.plans.find((p) => p.handle === getters.currentPlanHandle)
  },

  currentPlanHandle(state) {
    if (!state.subscriptionInfo) {
      return null
    }

    const subscriptionData = state.subscriptionInfo.subscriptionData

    // Check if we have an active plan
    for (const planHandle in subscriptionData) {
      if (subscriptionData[planHandle].status === 'active') {
        return planHandle
      }
    }

    // Check if we have an expiring plan
    for (const planHandle in subscriptionData) {
      if (subscriptionData[planHandle].status === 'expiring') {
        return planHandle
      }
    }

    // Otherwise assume we're on basic
    return 'basic'
  },

  subscriptionInfoPlan(state) {
    return (planHandle) => {
      if (!state.subscriptionInfo.plans[planHandle]) {
        return null
      }

      return state.subscriptionInfo.plans[planHandle]
    }
  },

  subscriptionInfoSubscriptionData(state) {
    return (planHandle) => {
      if (!state.subscriptionInfo.subscriptionData[planHandle]) {
        return null
      }

      return state.subscriptionInfo.subscriptionData[planHandle]
    }
  },

  proPlan(state) {
    return state.plans[1]
  }
}

/**
 * Actions
 */
const actions = {
  cancelSubscription({commit}, subscriptionUid) {
    return developerSupportApi.cancelSubscription(subscriptionUid)
      .then((response) => {
        if (response.data.error) {
          throw response.data.error
        }

        commit('updateSubscriptionInfo', response.data)
      })
  },

  getSubscriptionInfo({commit}) {
    return developerSupportApi.getSubscriptionInfo()
      .then((response) => {
        if (response.data.error) {
          throw response.data.error
        }

        commit('updateSubscriptionInfo', response.data)
      })
  },

  reactivateSubscription({commit}, subscriptionUid) {
    return developerSupportApi.reactivateSubscription(subscriptionUid)
      .then((response) => {
        if (response.data.error) {
          throw response.data.error
        }

        commit('updateSubscriptionInfo', response.data)
      })
  },

  subscribe({commit}, planHandle) {
    return developerSupportApi.subscribe(planHandle)
      .then((response) => {
        if (response.data.error) {
          throw response.data.error
        }

        commit('updateSubscriptionInfo', response.data)
      })
  },

  switchPlan({commit}, newPlan) {
    return developerSupportApi.switchPlan(newPlan)
      .then((response) => {
        if (response.data.error) {
          throw response.data.error
        }

        commit('updateSubscriptionInfo', response.data)
      })
  },
}

/**
 * Mutations
 */
const mutations = {
  updateSubscriptionInfo(state, subscriptionInfo) {
    state.subscriptionInfo = subscriptionInfo
  },
}

export default {
  namespaced: true,
  state,
  getters,
  actions,
  mutations
}
