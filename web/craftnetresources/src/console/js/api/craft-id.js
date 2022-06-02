/* global Craft */

import axios from 'axios'

export default {
    getCountries() {
        return axios.get(Craft.actionUrl + '/craftnet/console/craft-id/countries')
    }
}
