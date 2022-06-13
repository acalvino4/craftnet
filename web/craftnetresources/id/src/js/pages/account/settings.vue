<template>
  <form
    v-if="userDraft"
    @submit.prevent="save()">
    <h1>Settings</h1>
    <div class="card mb-6">
      <div class="card-body">
        <h4>Email &amp; password</h4>

        <textbox
          type="password"
          id="password"
          label="Current Password"
          v-model="password"
          :errors="errors.currentPassword" />
        <textbox
          id="email"
          label="Email"
          v-model="userDraft.email"
          :errors="errors.email" />
        <textbox
          type="password"
          id="newPassword"
          label="New Password"
          v-model="newPassword"
          :errors="errors.newPassword" />
      </div>
    </div>

    <div class="card mb-6">
      <div class="card-body">
        <h4>Account Settings</h4>

        <textbox
          id="username"
          label="Username"
          v-model="userDraft.username"
          :errors="errors.username" />

        <p>
          <input
            id="enablePluginDeveloperFeatures"
            :disabled="userIsInGroup('developers')"
            type="checkbox"
            name="fields[enablePluginDeveloperFeatures]"
            v-model="userDraft.enablePluginDeveloperFeatures">
          <label
            for="enablePluginDeveloperFeatures"
            :class="userIsInGroup('developers') ? 'disabled' : ''">Enable plugin
            developer features</label>
        </p>

        <p v-if="userIsInGroup('staff')">
          <input
            id="enableShowcaseFeatures"
            type="checkbox"
            name="fields[enableShowcaseFeatures]"
            v-model="userDraft.enableShowcaseFeatures">
          <label for="enableShowcaseFeatures">Enable showcase features</label>
        </p>
        <input
          v-else
          type="hidden"
          name="fields[enableShowcaseFeatures]"
          v-model="userDraft.enableShowcaseFeatures" />

        <input
          type="hidden"
          name="fields[enablePartnerFeatures]"
          v-model="userDraft.enablePartnerFeatures" />
      </div>
    </div>

    <btn
      kind="primary"
      type="submit"
      :disabled="loading"
      :loading="loading">Save
    </btn>
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
          enableShowcaseFeatures: (this.userDraft.enableShowcaseFeatures ? 1 : 0),
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
