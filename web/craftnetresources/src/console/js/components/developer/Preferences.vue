<template>
  <form
    v-if="userDraft"
    @submit.prevent="save()">
    <pane>
      <h2>Preferences</h2>

      <checkbox
        class="mt-2"
        label="Enable plugin developer features"
        id="enablePluginDeveloperFeatures"
        :disabled="userIsInGroup('developers')"
        v-model="userDraft.enablePluginDeveloperFeatures" />

      <div class="mt-4">
        <btn
          kind="primary"
          type="submit"
          :disabled="loading"
          :loading="loading">Save
        </btn>
      </div>
    </pane>
  </form>
</template>

<script>
import {mapState, mapGetters} from 'vuex'

export default {
  data() {
    return {
      loading: false,
      photoLoading: false,
      userDraft: {},
      password: '',
      newPassword: '',
      errors: {},
    }
  },

  computed: {
    ...mapState({
      user: state => state.account.user,
    }),

    ...mapGetters({
      userIsInGroup: 'account/userIsInGroup',
    }),
  },

  methods: {
    /**
     * Save the settings.
     */
    save() {
      this.loading = true

      let newEmail = false

      if (this.user.email !== this.userDraft.email) {
        newEmail = true
      }

      this.$store.dispatch('account/saveUser', {
          id: this.userDraft.id,
          email: this.userDraft.email,
          username: this.userDraft.username,
          enablePluginDeveloperFeatures: (this.userDraft.enablePluginDeveloperFeatures ? 1 : 0),
          enablePartnerFeatures: (this.userDraft.enablePartnerFeatures ? 1 : 0),
          password: this.password,
          newPassword: this.newPassword,
        })
        .then(() => {
          this.loading = false

          if (newEmail) {
            this.userDraft.email = this.user.email
            this.$store.dispatch('app/displayNotice', 'You’ve been sent an email to verify your new email address.')
          } else {
            this.$store.dispatch('app/displayNotice', 'Settings saved.')
          }

          this.password = ''
          this.newPassword = ''
          this.errors = {}
        })
        .catch(response => {
          this.loading = false

          const errorMessage = response.data && response.data.error ? response.data.error : 'Couldn’t save settings.'
          this.$store.dispatch('app/displayError', errorMessage)

          this.errors = response.data && response.data.errors ? response.data.errors : {}
        })
    }
  },

  mounted() {
    this.userDraft = JSON.parse(JSON.stringify(this.user))
  }
}
</script>
