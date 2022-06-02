/* global Craft */

import axios from 'axios'
import FormDataHelper from '../helpers/form-data.js';
import qs from 'qs'

export default {
    claimCmsLicense(licenseKey) {
        const data = {
            key: licenseKey
        }

        return axios.post(Craft.actionUrl + '/craftnet/console/cms-licenses/claim', qs.stringify(data), {
            headers: {
                'X-CSRF-Token': Craft.csrfTokenValue,
            }
        })
    },

    claimCmsLicenseFile(licenseFile) {
        const formData = new FormData();

        FormDataHelper.append(formData, 'licenseFile', licenseFile);

        return axios.post(Craft.actionUrl + '/craftnet/console/cms-licenses/claim', formData, {
            headers: {
                'X-CSRF-Token': Craft.csrfTokenValue,
            }
        })
    },

    getCmsLicense(id) {
        return axios.get(Craft.actionUrl + '/craftnet/console/cms-licenses/get-license-by-id', {params: {id}})
    },

    getExpiringCmsLicensesTotal() {
        return axios.get(Craft.actionUrl + '/craftnet/console/cms-licenses/get-expiring-licenses-total')
    },

    releaseCmsLicense(licenseKey) {
        const data = {
            key: licenseKey
        }

        return axios.post(Craft.actionUrl + '/craftnet/console/cms-licenses/release', qs.stringify(data), {
            headers: {
                'X-CSRF-Token': Craft.csrfTokenValue,
            }
        })
    },

    saveCmsLicense(license) {
        const data = license

        return axios.post(Craft.actionUrl + '/craftnet/console/cms-licenses/save', qs.stringify(data), {
            headers: {
                'X-CSRF-Token': Craft.csrfTokenValue,
            }
        })
    },
}