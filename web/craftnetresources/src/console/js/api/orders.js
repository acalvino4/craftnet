/* global VUE_APP_URL_CONSOLE */

import axios from './axios';
import qs from 'qs';

export default {
  getOrders(organizationId) {
    const query = qs.stringify({
      orgId: organizationId,
    })

    return axios.get(VUE_APP_URL_CONSOLE + '/orders?' + query)
  },

  getPendingOrders(organizationId) {
    const query = qs.stringify({
      orgId: organizationId,
      approvalPending: 1,
    })

    return axios.get(VUE_APP_URL_CONSOLE + '/orders?' + query)
  },

  requestOrderApproval({organizationId, orderNumber}) {
    return axios.post(VUE_APP_URL_CONSOLE + '/orders/' + orderNumber + '/request-approval', {
      orgId: organizationId
    })
  },

  rejectRequest({organizationId, orderNumber}) {
    return axios.post(VUE_APP_URL_CONSOLE + '/orders/' + orderNumber + '/reject-request', {
      orgId: organizationId
    })
  },
}
