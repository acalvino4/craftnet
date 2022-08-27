import {createStore} from 'vuex'
import addresses from './modules/addresses'
import account from './modules/account'
import app from './modules/app'
import apps from './modules/apps'
import cart from './modules/cart'
import cmsLicenses from './modules/cms-licenses'
import craftId from './modules/craft-id'
import developerSupport from './modules/developer-support'
import invoices from './modules/invoices'
import partner from './modules/partner'
import paymentMethods from './modules/payment-methods'
import pluginLicenses from './modules/plugin-licenses'
import plugins from './modules/plugins'
import pluginStore from './modules/plugin-store'
import stripe from './modules/stripe'
import organizations from './modules/organizations'

const store = createStore({
  strict: true,
  modules: {
    addresses,
    account,
    app,
    apps,
    cart,
    cmsLicenses,
    craftId,
    developerSupport,
    invoices,
    partner,
    paymentMethods,
    pluginLicenses,
    plugins,
    pluginStore,
    stripe,
    organizations,
  }
})

export default store