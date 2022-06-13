/* global Craft */

import axios from 'axios'
import qs from 'qs'

export default {
  cancelSubscription(subscription) {
    const data = {
      subscription
    }

    return axios.post(Craft.actionUrl + '/craftnet/console/developer-support/cancel-subscription', qs.stringify(data), {
      headers: {
        'X-CSRF-Token': Craft.csrfTokenValue,
      }
    })
  },

  getSubscriptionInfo() {
    return axios.get(Craft.actionUrl + '/craftnet/console/developer-support/get-subscription-info')
  },

  reactivateSubscription(subscription) {
    const data = {
      subscription
    }

    return axios.post(Craft.actionUrl + '/craftnet/console/developer-support/reactivate-subscription', qs.stringify(data), {
      headers: {
        'X-CSRF-Token': Craft.csrfTokenValue,
      }
    })
  },

  subscribe(plan) {
    const data = {
      plan
    }

    return axios.post(Craft.actionUrl + '/craftnet/console/developer-support/subscribe', qs.stringify(data), {
      headers: {
        'X-CSRF-Token': Craft.csrfTokenValue,
      }
    })
  },

  switchPlan(plan) {
    const data = {
      plan
    }

    return axios.post(Craft.actionUrl + '/craftnet/console/developer-support/switch-plan', qs.stringify(data), {
      headers: {
        'X-CSRF-Token': Craft.csrfTokenValue,
      }
    })
  }
}
