<template>
  <site-pane>
    <page-header>
      <div>
        <h1 class="mb-0">Forgot your password?</h1>
        <p class="mt-4">Enter your email below to receive your password
          reset instructions.</p>
      </div>
    </page-header>

    <form @submit.prevent="submit()">
      <field
        :vertical="true"
        label="Username or email"
        label-for="loginName">
        <textbox
          id="loginName"
          v-model="loginName"
          ref="loginName" />
      </field>

      <div class="mt-3">
        <btn
          kind="primary"
          type="submit"
          :loading="loading"
          :disabled="loading || v$.$invalid"
          block
          large>Send reset
          email
        </btn>
      </div>

      <div class="mt-3 text-sm">
        <p>
          <router-link to="/login">Sign in to your account
          </router-link>
          or
          <router-link to="/register">register</router-link>
          .
        </p>
      </div>
    </form>
  </site-pane>
</template>

<script>
import usersApi from '../api/users'
import FormDataHelper from '../helpers/form-data.js'
import helpers from '../mixins/helpers.js'
import useVuelidate from '@vuelidate/core'
import {required} from '@vuelidate/validators'
import SitePane from '@/console/js/components/site/SitePane';

export default {
  components: {SitePane},
  setup() {
    return {v$: useVuelidate()}
  },

  mixins: [helpers],

  data() {
    return {
      loading: false,
      loginName: '',
    }
  },

  validations: {
    loginName: {required},
  },

  methods: {
    submit() {
      this.loading = true

      let formData = new FormData()

      FormDataHelper.append(formData, 'loginName', this.loginName)

      usersApi.sendPasswordResetEmail(formData)
        .then(response => {
          this.loading = false

          if (response.data.error) {
            this.$store.dispatch('app/displayError', response.data.error)
          } else {
            this.loginName = ''
            const loginNameInput = this.$refs.loginName.$el.querySelector('input')
            loginNameInput.blur()
            this.$store.dispatch('app/displayNotice', 'Password reset email sent.')
          }
        })
        .catch(() => {
          this.loading = false
          this.$store.dispatch('app/displayError', 'Couldn’t send reset email.')
        });
    }
  }
}
</script>
