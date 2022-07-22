<template>
  <div
    :class="{'has-sidebar': (!$route.meta.layout || $route.meta.layout !== 'no-sidebar')}">
    <auth-manager ref="authManager"></auth-manager>

    <template v-if="notification">
      <div
        id="notifications-wrapper"
        :class="{'hide': !notification }">
        <div id="notifications">
          <div
            class="notification"
            :class="notification.type">
            {{ notification.message }}
          </div>
        </div>
      </div>
    </template>

    <template v-if="loading">
      <div class="mt-24 text-center">
        <spinner
          size="lg"
          class="mt-8"></spinner>
      </div>
    </template>

    <template v-else>
      <component :is="layoutComponent"></component>
    </template>
  </div>
</template>

<script>
import {mapGetters, mapState} from 'vuex'
import helpers from './mixins/helpers.js'
import AuthManager from './components/AuthManager'
import AppLayout from './components/layouts/AppLayout'
import SiteLayout from './components/layouts/SiteLayout'

export default {
  mixins: [helpers],

  components: {
    AuthManager,
    AppLayout,
    SiteLayout,
  },

  computed: {
    ...mapState({
      notification: state => state.app.notification,
      organizations: state => state.organizations.organizations,
      loading: state => state.app.loading,
      user: state => state.account.user,
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

        default:
          return 'app-layout'
      }
    }
  },

  methods: {
    initRouterBeforeEach() {
      const vueApp = this;

      // Make things happen before each route change
      this.$router.beforeEach((to, from, next) => {
        if (!vueApp.$refs) {
          return
        }

        // Renew the auth manager’s session
        if (vueApp.$refs.authManager) {
          vueApp.$refs.authManager.renewSession()
        }

        // Load the user
        if (!vueApp.$store.state.account.user) {
          vueApp.$store.dispatch('account/loadAccount')
            .then(() => {
              // Load the cart
              this.$store.dispatch('cart/getCart')
                .then(() => {
                  // Load the current organization
                  this.$store.dispatch('organizations/getCurrentOrganizationId')
                    .then(() => {
                      this.$store.commit('app/updateLoading', false)

                      if (vueApp.$store.state.account.user) {
                        // Logged in user

                        // Renew the auth manager’s session if needed
                        if (vueApp.$refs.authManager) {
                          vueApp.$refs.authManager.renewSession()
                        }

                        next()
                      } else {
                        // Guest user
                        // Check that the user can access the next route
                        if (!to.meta.allowAnonymous) {
                          next({path: '/login'})
                        } else {
                          next()
                        }
                      }
                    })
                })
            })
        } else {
          next()
        }
      })
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
