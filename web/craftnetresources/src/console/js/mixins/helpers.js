/* global VUE_APP_NODE_ENV, VUE_APP_BASE_URL, VUE_APP_CRAFT_PLUGINS_URL */

import get from 'lodash/get'
import update from 'lodash/update'
import {parseDate} from "../filters/date.js";
import {mapGetters} from 'vuex';

export default {
  computed: {
    ...mapGetters({
      currentOrganization: 'organizations/currentOrganization'
    }),
  },
  methods: {
    /**
     * Clones an object without references or bindings.
     * Optionally accepts a filtered property list with dot-syntax
     * for nested properties.
     *
     * Example:
     * ```
     * let obj = {
     *     test: 'value',
     *     foo: {
     *         bar: {
     *             baz: 'one',
     *             boo: 'two'
     *         }
     *     }
     * }
     *
     * // an existing value and a missing value, with default
     * let clone = simpleClone(obj, [
     *     'foo.bar.baz',
     *     ['aList', []]
     * ])
     *
     * clone == {foo: {bar: {baz: 'hello'}}, aList: []} // true
     * ```
     *
     * @param {Object} obj
     * @param {Array} propertyList
     */
    simpleClone(obj, propertyList) {
      const clone = JSON.parse(JSON.stringify(obj))

      if (!propertyList) {
        return clone
      }

      const filteredClone = {}

      for (let i = 0; i < propertyList.length; i++) {
        const path = propertyList[i];

        if (typeof path === 'object') {
          update(filteredClone, path, () => get(clone, path[0], path[1]))
        } else {
          update(filteredClone, path, () => get(clone, path, null))
        }
      }

      return filteredClone;
    },

    /**
     * Returns a static image URL.
     *
     * @param {String} url
     * @returns {String}
     */
    staticImageUrl(url) {
      if (VUE_APP_NODE_ENV === 'development' && VUE_APP_BASE_URL) {
        return VUE_APP_BASE_URL + 'img/static/' + url;
      }

      return '/craftnetresources/dist/img/static/' + url;
    },

    /**
     * Returns the Craft Plugins URL.
     *
     * @returns {String}
     */
    craftPluginsUrl() {
      return VUE_APP_CRAFT_PLUGINS_URL;
    },

    expiresSoon(license) {
      if (!license.expiresOn) {
        return false
      }

      const today = new Date()
      const expiryDate = new Date()
      expiryDate.setDate(today.getDate() + 45)

      const expiresOn = new Date(license.expiresOn.date)

      if (expiryDate > expiresOn) {
        return true
      }

      return false
    },

    daysBeforeExpiry(license) {
      const today = new Date()
      const expiresOn = new Date(license.expiresOn.date)
      const diff = expiresOn.getTime() - today.getTime()
      const diffDays = Math.round(diff / (1000 * 60 * 60 * 24))
      return diffDays;
    },

    getRenewableLicenses(license, renew, cartItems) {
      const renewableLicenses = []

      // CMS license
      const renewalOptions = license.renewalOptions
      const renewalOption = renewalOptions[renew]
      const expiryDate = renewalOption.expiryDate

      if (license.expirable) {
        renewableLicenses.push({
          type: 'cms-renewal',
          key: license.key,
          description: 'Craft ' + license.editionDetails.name,
          renew: renew,
          expiryDate: expiryDate,
          expiresOn: license.expiresOn,
          edition: license.editionDetails,
          alreadyInCart: this.licenseKeyAlreadyInCart(license.key, cartItems),
          amount: renewalOption.amount
        })
      }

      // Plugin licenses
      if (license.pluginLicenses.length > 0) {
        // Renewable plugin licenses
        const renewablePluginLicenses = license.pluginLicenses.filter((pluginLicense) => {
          // Ignore the plugin licenses that don’t have a renewal option
          if (!license.pluginRenewalOptions[pluginLicense.key]) {
            return false
          }

          // Ignore the plugin licenses are not renewable
          if (!pluginLicense.expiresOn) {
            return false
          }

          // Ignore the plugin licenses that expire after the CMS license
          if (!pluginLicense.expired) {
            const pluginExpiresOn = parseDate(pluginLicense.expiresOn.date)
            const expiryDateObject = parseDate(expiryDate)

            if (expiryDateObject.diff(pluginExpiresOn) <= 0) {
              return false
            }
          }

          return true
        })

        // Add renewable plugin licenses to the `renewableLicenses` array
        renewablePluginLicenses.forEach(function(renewablePluginLicense) {
          const pluginRenewalOptionKey = renewablePluginLicense.key
          const pluginRenewalOptions = license.pluginRenewalOptions[pluginRenewalOptionKey]
          const pluginRenewalOption = pluginRenewalOptions.find((r) => r.expiryDate === expiryDate)
          const amount = pluginRenewalOption.amount

          renewableLicenses.push({
            type: 'plugin-renewal',
            key: renewablePluginLicense.key,
            description: renewablePluginLicense.plugin.name,
            expiryDate: expiryDate,
            expiresOn: renewablePluginLicense.expiresOn,
            edition: renewablePluginLicense.edition,

            alreadyInCart: this.licenseKeyAlreadyInCart(renewablePluginLicense.key, cartItems),
            amount: amount,
          })
        }.bind(this))
      }

      return renewableLicenses
    },

    licenseKeyAlreadyInCart(licenseKey, cartItems) {
      return !!cartItems.find((item) => item.lineItem.options.licenseKey === licenseKey)
    },

    getPrefixedTo(to) {
      return (this.currentOrganization ? '/organizations/' + this.currentOrganization.slug : '') + to
    }
  }
}
