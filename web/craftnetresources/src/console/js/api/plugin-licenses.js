/* global Craft */

import axios from 'axios'
import qs from 'qs'

export default {
    claimPluginLicense(licenseKey) {
        const data = {
            key: licenseKey
        }

        return axios.post(Craft.actionUrl + '/craftnet/console/plugin-licenses/claim', qs.stringify(data), {
            headers: {
                'X-CSRF-Token': Craft.csrfTokenValue,
            }
        })
    },

    getPluginLicense(id) {
        return axios.get(Craft.actionUrl + '/craftnet/console/plugin-licenses/get-license-by-id', {params: {id}})
    },

    getExpiringPluginLicensesTotal() {
        return axios.get(Craft.actionUrl + '/craftnet/console/plugin-licenses/get-expiring-licenses-total')
    },

    releasePluginLicense({pluginHandle, licenseKey}) {
        const data = {
            handle: pluginHandle,
            key: licenseKey
        }

        return axios.post(Craft.actionUrl + '/craftnet/console/plugin-licenses/release', qs.stringify(data), {
            headers: {
                'X-CSRF-Token': Craft.csrfTokenValue,
            }
        })
    },

    savePluginLicense(license) {
        const data = {};

        for (const attribute in license) {
            if (attribute === 'cmsLicense') {
                continue
            }

            data[attribute] = license[attribute]
        }

        return axios.post(Craft.actionUrl + '/craftnet/console/plugin-licenses/save', qs.stringify(data), {
            headers: {
                'X-CSRF-Token': Craft.csrfTokenValue,
            }
        })
    },
}