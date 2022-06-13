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
      path: '/settings',
      redirect: '/settings/profile',
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
      name: 'AccountProfile',
      component: () => import('../pages/settings/profile.vue'),
    },
    {
      path: '/settings/members',
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
      path: '/settings/billing',
      name: 'Billing',
      component: () => import('../pages/settings/billing'),
    },
    {
      path: '/settings/billing/invoices/:number',
      name: 'AccountBillingInvoiceNumber',
      component: () => import('../pages/settings/billing/invoices/_number.vue'),
    },
    {
      path: '/settings/developer',
      name: 'SettingsDeveloper',
      component: () => import('../pages/settings/developer.vue'),
    },
    {
      path: '/settings/developer-support',
      name: 'AccountDeveloperSupport',
      component: () => import('../pages/settings/developer-support'),
    },
    {
      path: '/settings/developer-support/old',
      name: 'AccountDeveloperSupportOld',
      component: () => import('../pages/settings/developer-support/old.vue'),
    },
    {
      path: '/settings/partner',
      redirect: '/settings/partner/overview',
    },
    {
      path: '/settings/partner/overview',
      name: 'PartnerOverview',
      component: () => import('../pages/settings/partner/overview.vue'),
    },
    {
      path: '/settings/partner/profile',
      name: 'PartnerProfile',
      component: () => import('../pages/settings/partner/profile.vue'),
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
      path: '/developer/plugins',
      name: 'Plugins',
      component: () => import('../pages/developer/plugins'),
      meta: {stripeAccountAlert: true}
    },
    {
      path: '/developer/add-plugin',
      component: () => import('../pages/developer/plugins/_id.vue'),
    },
    {
      path: '/developer/plugins/:id',
      name: 'DeveloperPluginsId',
      component: () => import('../pages/developer/plugins/_id.vue'),
    },
    {
      path: '/developer/sales',
      name: 'DeveloperSalesIndex',
      component: () => import('../pages/developer/sales'),
      meta: {stripeAccountAlert: true}
    },
    {
      path: '/licenses/cms',
      component: () => import('../pages/licenses/cms'),
      meta: {cmsLicensesRenewAlert: true}
    },
    {
      path: '/licenses/cms/:id',
      component: () => import('../pages/licenses/cms/_id.vue'),
    },
    {
      path: '/licenses/plugins',
      component: () => import('../pages/licenses/plugins'),
      meta: {pluginLicensesRenewAlert: true}
    },
    {
      path: '/licenses/plugins/:id',
      component: () => import('../pages/licenses/plugins/_id.vue'),
    },
    {
      path: '/licenses/claim',
      component: () => import('../pages/licenses/claim.vue'),
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
      component: () => import('../pages/payment.vue'),
      meta: {sidebar: false, allowAnonymous: true}
    },
    {
      path: '/thank-you',
      name: 'ThankYou',
      component: () => import('../pages/thank-you.vue'),
      meta: {sidebar: false, allowAnonymous: true}
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
