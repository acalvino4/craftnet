import {createWebHistory, createRouter} from 'vue-router'

const router = createRouter({
  mode: 'history',
  history: createWebHistory(),
  linkActiveClass: 'active',

  scrollBehavior(to, from, savedPosition) {
    return savedPosition || {x: 0, y: 0}
  },
  routes: [
    // Redirects

    {
      path: '/',
      redirect: '/licenses',
    },
    {
      path: '/licenses',
      redirect: '/licenses/cms',
    },
    {
      path: '/developer',
      redirect: '/developer/sales',
    },
    {
      path: '/organizations/:orgSlug',
      redirect: to => {
        return '/organizations/' + to.params.orgSlug + '/licenses/cms'
      },
    },


    // Pages

    {
      path: '/register',
      name: 'Register',
      component: () => import('../pages/register'),
      meta: {layout: 'site', centerContent: true, allowAnonymous: true}
    },
    {
      path: '/register/success',
      name: 'RegisterSuccess',
      component: () => import('../pages/register/success.vue'),
      meta: {layout: 'site', centerContent: true, allowAnonymous: true}
    },
    {
      path: '/login',
      name: 'Login',
      component: () => import('../pages/login.vue'),
      meta: {layout: 'site', centerContent: true, mainFull: true, allowAnonymous: true}
    },
    {
      path: '/forgot-password',
      name: 'ForgotPassword',
      component: () => import('../pages/forgot-password.vue'),
      meta: {layout: 'site', centerContent: true, allowAnonymous: true}
    },

    {
      path: '/settings/account',
      name: 'AccountSettings',
      component: () => import('../pages/settings/account.vue'),
    },
    {
      path: '/settings/profile',
      name: 'UserProfile',
      component: () => import('../pages/settings/profile'),
      meta: {orgFallbackRoute: 'OrgProfile'}
    },

    {
      path: '/organizations/:orgSlug/settings/profile',
      name: 'OrgProfile',
      component: () => import('../pages/settings/profile'),
      meta: {userFallbackRoute: 'UserProfile'}
    },

    {
      path: '/organizations/:orgSlug/settings/members',
      name: 'SettingsMembers',
      component: () => import('../pages/settings/members.vue'),
    },
    {
      path: '/settings/organizations',
      name: 'SettingsOrganizations',
      component: () => import('../pages/settings/organizations'),
    },
    {
      path: '/settings/organizations/new',
      name: 'SettingsOrganizationsEdit',
      component: () => import('../pages/settings/organizations/_edit.vue'),
    },
    {
      path: '/settings/orders',
      name: 'UserOrders',
      component: () => import('../pages/settings/orders/index.vue'),
      meta: {orgFallbackRoute: 'OrgOrders'}
    },
    {
      path: '/organizations/:orgSlug/settings/orders',
      name: 'OrgOrders',
      component: () => import('../pages/settings/orders/index.vue'),
      meta: {userFallbackRoute: 'UserOrders'}
    },
    {
      path: '/settings/billing',
      name: 'UserBilling',
      component: () => import('../pages/settings/billing'),
      meta: {orgFallbackRoute: 'OrgBilling'}
    },
    {
      path: '/organizations/:orgSlug/settings/billing',
      name: 'OrgBilling',
      component: () => import('../pages/settings/billing'),
      meta: {userFallbackRoute: 'UserBilling'}
    },
    {
      path: '/settings/orders/:number',
      name: 'UserOrderDetails',
      component: () => import('../pages/settings/orders/_number.vue'),
    },
    {
      path: '/organizations/:orgSlug/settings/orders/:number',
      name: 'OrgOrderDetails',
      component: () => import('../pages/settings/orders/_number.vue'),
    },
    {
      path: '/organizations/:orgSlug/settings/orders/:number/review',
      name: 'OrgOrderReview',
      component: () => import('../pages/settings/orders/_number.vue'),
    },
    {
      path: '/organizations/:orgSlug/settings/plugin-store',
      name: 'SettingsPluginStore',
      component: () => import('../pages/settings/plugin-store.vue'),
      meta: {orgOnly: true}
    },
    {
      path: '/settings/developer-support',
      name: 'AccountDeveloperSupport',
      component: () => import('../pages/settings/developer-support'),
      meta: {userOnly: true}
    },
    {
      path: '/settings/developer-support/old',
      name: 'AccountDeveloperSupportOld',
      component: () => import('../pages/settings/developer-support/old.vue'),
    },
    {
      path: '/organizations/:orgSlug/settings/partner',
      redirect: '/settings/partner/overview',
    },
    {
      path: '/organizations/:orgSlug/settings/partner/overview',
      name: 'PartnerOverview',
      component: () => import('../pages/settings/partner/overview.vue'),
    },
    {
      path: '/organizations/:orgSlug/settings/partner/network',
      name: 'PartnerNetwork',
      component: () => import('../pages/settings/partner/network.vue'),
    },
    {
      path: '/buy-plugin/:handle/:edition',
      name: 'BuyPlugin',
      component: () => import('../pages/buy-plugin'),
      meta: {sidebar: false, allowAnonymous: true}
    },
    {
      path: '/buy-cms/:edition',
      name: 'BuyCms',
      component: () => import('../pages/buy-cms'),
      meta: {sidebar: false, allowAnonymous: true}
    },
    {
      path: '/cart',
      name: 'Cart',
      component: () => import('../pages/cart'),
      meta: {sidebar: false, allowAnonymous: true}
    },
    {
      path: '/cart/old',
      name: 'CartOld',
      component: () => import('../pages/cart/old.vue'),
      meta: {sidebar: false, allowAnonymous: true}
    },
    {
      path: '/organizations/:orgSlug/developer/plugins',
      name: 'Plugins',
      component: () => import('../pages/developer/plugins'),
      meta: {stripeAccountAlert: true}
    },
    {
      path: '/organizations/:orgSlug/developer/add-plugin',
      component: () => import('../pages/developer/plugins/_id.vue'),
    },
    {
      path: '/organizations/:orgSlug/developer/plugins/:id',
      name: 'DeveloperPluginsId',
      component: () => import('../pages/developer/plugins/_id.vue'),
    },
    {
      path: '/organizations/:orgSlug/developer/sales',
      name: 'DeveloperSalesIndex',
      component: () => import('../pages/developer/sales'),
      meta: {stripeAccountAlert: true}
    },
    {
      path: '/licenses/cms',
      name: 'UserCmsLicenses',
      component: () => import('../pages/licenses/cms'),
      meta: {orgFallbackRoute: 'OrgCmsLicenses', cmsLicensesRenewAlert: true}
    },
    {
      path: '/organizations/:orgSlug/licenses/cms',
      name: 'OrgCmsLicenses',
      component: () => import('../pages/licenses/cms'),
      meta: {userFallbackRoute: 'UserCmsLicenses', cmsLicensesRenewAlert: true}

    },
    {
      path: '/licenses/cms/:id',
      component: () => import('../pages/licenses/cms/_id.vue'),
    },
    {
      path: '/organizations/:orgSlug/licenses/cms/:id',
      component: () => import('../pages/licenses/cms/_id.vue'),
    },
    {
      path: '/licenses/plugins',
      name: 'UserPluginsLicenses',
      component: () => import('../pages/licenses/plugins'),
      meta: {orgFallbackRoute: 'OrgPluginsLicenses', pluginLicensesRenewAlert: true}
    },
    {
      path: '/organizations/:orgSlug/licenses/plugins',
      name: 'OrgPluginsLicenses',
      component: () => import('../pages/licenses/plugins'),
      meta: {userFallbackRoute: 'UserPluginsLicenses', pluginLicensesRenewAlert: true}
    },
    {
      path: '/licenses/plugins/:id',
      component: () => import('../pages/licenses/plugins/_id.vue'),
    },
    {
      path: '/organizations/:orgSlug/licenses/plugins/:id',
      component: () => import('../pages/licenses/plugins/_id.vue'),
    },
    {
      path: '/licenses/claim',
      name: 'UserClaimLicenses',
      component: () => import('../pages/licenses/claim.vue'),
      meta: {orgFallbackRoute: 'OrgClaimLicenses'}

    },
    {
      path: '/organizations/:orgSlug/licenses/claim',
      name: 'OrgClaimLicenses',
      component: () => import('../pages/licenses/claim.vue'),
      meta: {userFallbackRoute: 'UserClaimLicenses'}
    },
    {
      path: '/identity',
      name: 'Identity',
      component: () => import('../pages/identity.vue'),
      meta: {sidebar: false, allowAnonymous: true}
    },
    {
      path: '/payment',
      name: 'Payment',
      component: () => import('../pages/payment/index.vue'),
      meta: {layout: 'checkout', sidebar: false}
    },
    {
      path: '/payment/old',
      name: 'PaymentOld',
      component: () => import('../pages/payment/old.vue'),
      meta: {sidebar: false, allowAnonymous: true}
    },
    {
      path: '/thank-you',
      name: 'ThankYou',
      component: () => import('../pages/thank-you.vue'),
      meta: {layout: 'checkout'}
    },
    {
      path: '/approval-requested',
      name: 'ApprovalRequested',
      component: () => import('../pages/approval-requested.vue'),
      meta: {layout: 'checkout'}
    },

    // Not found
    {
      path: '/:pathMatch(.*)*',
      name: 'NotFound',
      component: () => import('../pages/not-found.vue'),
      meta: {sidebar: false}
    },
  ]
})

export default router
