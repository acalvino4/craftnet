/* global Craft */

import _axios from 'axios'

const axios = _axios.create({
  headers: {
    'X-CSRF-Token': Craft.csrfTokenValue,
  }
})

export default axios