/* global Craft */

import axios from 'axios'
import qs from 'qs'
import FormDataHelper from '../helpers/form-data.js'

export default {
    saveBillingInfo(data) {
        return axios.post(Craft.actionUrl + '/craftnet/console/account/save-billing-info', qs.stringify(data), {
            headers: {
                'X-CSRF-Token': Craft.csrfTokenValue,
            }
        })
    },

    uploadUserPhoto(data) {
        const formData = new FormData()

        for (const attribute in data) {
            FormDataHelper.append(formData, attribute, data[attribute])
        }

        return axios.post(Craft.actionUrl + '/craftnet/console/account/upload-user-photo', formData, {
            headers: {
                'X-CSRF-Token': Craft.csrfTokenValue,
            }
        })
    },

    deleteUserPhoto() {
        return axios.post(Craft.actionUrl + '/craftnet/console/account/delete-user-photo', {}, {
            headers: {
                'X-CSRF-Token': Craft.csrfTokenValue,
            }
        })
    },

    generateApiToken() {
        return axios.post(Craft.actionUrl + '/craftnet/console/account/generate-api-token', {}, {
            headers: {
                'X-CSRF-Token': Craft.csrfTokenValue,
            }
        })
    },

    getAccount() {
        return axios.post(Craft.actionUrl + '/craftnet/console/account/get-account', {}, {
            headers: {
                'X-CSRF-Token': Craft.csrfTokenValue,
            }
        })
    }
}
