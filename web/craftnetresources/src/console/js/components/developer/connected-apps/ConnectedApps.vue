<template>
  <div>
    <template v-if="appsLoading">
      <spinner></spinner>
    </template>
    <template v-else>
      <div>
        <stripe-app v-if="showStripe"></stripe-app>

        <template
          v-for="(appType, appIndex) in appTypes"
          :key="appIndex">
          <connected-app
            class="border-t mt-6 pt-6"
            :name="appType.name"
            :description="'Connect to your ' + appType.name + ' account.'"
            :icon="staticImageUrl('' + appType.handle + '.svg')"
            :account-name="accountName(appType.handle)"
            :connected="apps[appType.handle]"
            :buttonLoading="(loading && loading[appType.handle])"
            @connect="connect(appType.handle)"
            @disconnect="disconnect(appType.handle)"
          ></connected-app>
        </template>
      </div>
    </template>
  </div>
</template>

<script>
import {mapState, mapGetters} from 'vuex'
import helpers from '../../../mixins/helpers.js'
import StripeApp from './StripeApp'
import ConnectedApp from './ConnectedApp'

export default {
  mixins: [helpers],

  props: ['title', 'showStripe'],

  data() {
    return {
      appTypes: [
        {
          handle: 'github',
          name: 'GitHub',
        },
        //{
        //	handle: 'bitbucket',
        //	name: 'BitBucket',
        //}
      ],
      loading: {
        bitbucket: false,
        github: false,
      },
    };
  },

  components: {
    StripeApp,
    ConnectedApp,
  },

  computed: {
    ...mapState({
      apps: state => state.apps.apps,
      appsLoading: state => state.apps.appsLoading,
      user: state => state.account.user,
    }),

    ...mapGetters({
      userIsInGroup: 'account/userIsInGroup',
    }),
  },

  methods: {
    /**
     * Account name.
     *
     * @param appType
     * @returns {*}
     */
    accountName(appType) {
      if (this.apps[appType]) {
        let app = this.apps[appType];
        switch (appType) {
          case 'github':
            return app.account.name;
          case 'bitbucket':
            return app.account.display_name;
        }
      }
    },

    /**
     * Connect.
     *
     * @param provider
     */
    connect(provider) {
      let width = 800;
      let height = 830;

      switch (provider) {
        case 'bitbucket':
          width = 1024;
          height = 570;
          break;
      }

      let winWidth = window.innerWidth ? window.innerWidth : document.documentElement.clientWidth ? document.documentElement.clientWidth : screen.width;
      let winHeight = window.innerHeight ? window.innerHeight : document.documentElement.clientHeight ? document.documentElement.clientHeight : screen.height;

      let left = ((winWidth / 2) - (width / 2));
      let top = ((winHeight / 2) - (height / 2));

      let url = '/apps/connect/' + provider;

      let name = 'ConnectWithOauth';
      let specs = 'location=0,status=0,width=' + width + ',height=' + height + ',left=' + left + ',top=' + top;

      window.open(url, name, specs);
    },

    /**
     * Disconnect.
     *
     * @param provider
     */
    disconnect(provider) {
      this.loading[provider] = true;
      this.$store.dispatch('apps/disconnectApp', provider)
        .then(() => {
          this.loading[provider] = false;
          this.$store.dispatch('app/displayNotice', 'App disconnected.');
        })
        .catch((response) => {
          this.loading[provider] = false;
          this.errors = response.data && response.data.errors ? response.data.errors : {};

          const errorMessage = response.data && response.data.error ? response.data.error : 'Couldn’t disconnect app.';
          this.$store.dispatch('app/displayError', errorMessage);
        });
    },
  },

  mounted() {
    this.$store.dispatch('apps/getApps')
  }
}
</script>
