<template>
  <div
    :class="{'has-sidebar': (!$route.meta.layout || $route.meta.layout !== 'no-sidebar')}">
    <auth-manager ref="authManager" />

    <template v-if="notification">
      <div
        id="notifications-wrapper"
        :class="{'hide': !notification }"
      >
        <div id="notifications">
          <div
            class="notification"
            :class="notification.type"
          >
            {{ notification.message }}
          </div>
        </div>
      </div>
    </template>

    <template v-if="loading">
      <div class="mt-24 text-center">
        <spinner
          size="lg"
          class="mt-8"
        />
      </div>
    </template>

    <template v-else>
      <component :is="layoutComponent" />
    </template>
  </div>
</template>

<script>
import {mapGetters, mapState} from 'vuex'
import helpers from './mixins/helpers.js'
import AuthManager from './components/AuthManager'
import AppLayout from './components/layouts/AppLayout'
import CheckoutLayout from './components/layouts/CheckoutLayout'
import SiteLayout from './components/layouts/SiteLayout'

export default {
  mixins: [helpers],

  components: {
    AuthManager,
    AppLayout,
    CheckoutLayout,
    SiteLayout,
  },

  computed: {
    ...mapState({
      notification: state => state.app.notification,
      organizations: state => state.organizations.organizations,
      loading: state => state.app.loading,
      user: state => state.account.user,
      currentOrgSlug: state => state.organizations.currentOrgSlug,
    }),

    ...mapGetters({
      currentOrganization: 'organizations/currentOrganization'
    }),

    layoutComponent() {
      if (!this.user) {
        return 'site-layout'
      }

      switch (this.$route.meta.layout) {
        case 'site':
          return 'site-layout'

        case 'checkout':
          return 'checkout-layout'

        default:
          return 'app-layout'
      }
    }
  },

  methods: {
    initRouterBeforeEach() {
      // Make things happen before each route change
      this.$router.beforeEach((to, from, next) => {
        if (!this.$refs) {
          return
        }

        // Renew the auth manager’s session
        if (this.$refs.authManager) {
          this.$refs.authManager.renewSession()
        }

        // Load the user
        this.$store.commit('app/updateLoading', true)
        this.loadAccount()
          .then(() => {
            this.loadCurrentOrg(to)
              .then(() => {
                this.handleRoute(to, from, next)
              })
          })
      })

      this.$router.afterEach(() => {
        this.$store.commit('app/updateLoading', false)
      })
    },

    loadAccount() {
      return new Promise((resolve, reject) => {
        // Load the user
        if (!this.$store.state.account.user) {
          this.$store.dispatch('account/loadAccount')
            .then(() => {
              this.$store.dispatch('cart/getCart')
                .then(() => {
                  resolve()
                })
                .catch(() => {
                  reject()
                })
            })
            .catch(() => {
              reject()
            })
        } else {
          resolve()
        }
      })
    },

    loadCurrentOrg(to) {
      return new Promise((resolve, reject) => {
        const currentOrgSlug = (to.params && to.params.orgSlug ? to.params.orgSlug : null);

        if (this.currentOrgSlug !== currentOrgSlug) {
          this.$store.commit('organizations/updateCurrentOrgSlug', currentOrgSlug)

          if (this.currentOrganization) {
            this.getOrgMembers()
              .then(() => {
                resolve()
              })
              .catch(() => {
                reject()
              })
          } else {
            resolve()
          }
        } else {
          resolve()
        }
      })
    },

    handleRoute(to, from, next) {
      if (this.$store.state.account.user) {
        // Logged in user

        // Renew the auth manager’s session if needed
        if (this.$refs.authManager) {
          this.$refs.authManager.renewSession()
        }

        if (to.meta.orgOnly) {
          if (this.currentOrganization) {
            next()
          } else {
            next({path: '/'})
          }
        } else if(to.meta.userOnly) {
          if (!this.currentOrganization) {
            next()
          } else {
            next({path: '/'})
          }
        } else {
          next()
        }
      } else {
        // Guest user
        // Check that the user can access the next route
        if (!to.meta.allowAnonymous) {
          next({path: '/login'})
        } else {
          next()
        }
      }
    },

    /**
     * Connect app callback.
     *
     * @param apps
     */
    connectAppCallback(apps) {
        this.$store.dispatch('apps/connectAppCallback', apps)

        this.$store.dispatch('app/displayNotice', 'App connected.')
    },

    getOrgMembers() {
      return this.$store.dispatch('organizations/getOrganizationMembers', {
        organizationId: this.currentOrganization.id
      })
    }
  },

  mounted() {
    this.initRouterBeforeEach()

    if (window.sessionNotice) {
      this.$store.dispatch('app/displayNotice', window.sessionNotice)
    }

    if (window.sessionError) {
      this.$store.dispatch('app/displayError', window.sessionError)
    }
  },

  expose: ['connectAppCallback']
}
</script>

<style lang="scss">
#app:not(.has-sidebar) {
  .header {
    #sidebar-toggle {
      @apply hidden;
    }
  }
}
</style>
