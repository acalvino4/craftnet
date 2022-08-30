import {createStore} from 'vuex'
import account from './modules/account'
import addresses from './modules/addresses'
import app from './modules/app'
import apps from './modules/apps'
import cart from './modules/cart'
import cmsLicenses from './modules/cms-licenses'
import craftId from './modules/craft-id'
import developerSupport from './modules/developer-support'
import invoices from './modules/invoices'
import orders from './modules/orders'
import organizations from './modules/organizations'
import partner from './modules/partner'
import paymentMethods from './modules/payment-methods'
import pluginLicenses from './modules/plugin-licenses'
import pluginStore from './modules/plugin-store'
import plugins from './modules/plugins'
import stripe from './modules/stripe'

const store = createStore({
  strict: true,
  modules: {
    account,
    addresses,
    app,
    apps,
    cart,
    cmsLicenses,
    craftId,
    developerSupport,
    invoices,
    orders,
    organizations,
    partner,
    paymentMethods,
    pluginLicenses,
    pluginStore,
    plugins,
    stripe,
  }
})

export default store