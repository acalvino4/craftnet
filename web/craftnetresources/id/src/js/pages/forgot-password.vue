<template>
  <div>
    <h1 class="mb-0">Forgot your password?</h1>
    <p class="lead">Enter your email below to receive your password reset
      instructions.</p>

    <form @submit.prevent="submit()">
      <textbox
        id="loginName"
        label="Username or email"
        v-model="loginName"
        ref="loginName" />

      <btn
        kind="primary"
        type="submit"
        :loading="loading"
        :disabled="loading || $v.$invalid"
        block
        large>Send reset email
      </btn>
    </form>

    <p class="mt-4">
      <router-link to="/login">Sign in to your account</router-link>
      or
      <router-link to="/register">register</router-link>
    </p>
  </div>
</template>

<script>
import {required} from 'vuelidate/lib/validators'
import usersApi from '../api/users'
import FormDataHelper from '../helpers/form-data'
import helpers from '../mixins/helpers'

export default {
  mixins: [helpers],

  data() {
    return {
      loading: false,
      loginName: '',
    }
  },

  validations: {
    loginName: {
      required,
    },
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
