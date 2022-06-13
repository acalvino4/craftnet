<template>
  <div>
    <h1 class="mb-0">Sign up</h1>
    <p class="lead">or
      <router-link to="/login">sign in to your account</router-link>
    </p>

    <form
      method="post"
      accept-charset="UTF-8"
      @submit.prevent="submit()"
      ref="registerform">
      <textbox
        id="username"
        label="Username"
        v-model="username"
        :errors="getFieldErrors('username')" />
      <textbox
        type="email"
        id="email"
        label="Email"
        v-model="email"
        :errors="getFieldErrors('email')" />
      <textbox
        type="password"
        id="password"
        label="Password"
        v-model="password"
        :errors="getFieldErrors('password')" />
      <btn
        kind="primary"
        type="submit"
        :loading="loading"
        :disabled="!formValidates() || loading"
        block
        large>Register
      </btn>
    </form>
  </div>
</template>

<script>
import usersApi from '../../api/users'
import helpers from '../../mixins/helpers'
import FormDataHelper from '../../helpers/form-data'

export default {
  mixins: [helpers],

  data() {
    return {
      loading: false,
      errors: {},
      username: '',
      email: '',
      password: '',
    }
  },

  methods: {
    submit() {
      this.loading = true

      if (!this.formValidates()) {
        this.$store.dispatch('app/displayError', 'Couldn’t login.')
        return false
      }

      // Send login request

      let formData = new FormData()

      FormDataHelper.append(formData, 'username', this.username)
      FormDataHelper.append(formData, 'email', this.email)
      FormDataHelper.append(formData, 'password', this.password)

      usersApi.registerUser(formData)
        .then(response => {
          this.loading = false

          if (response.data.errors) {
            this.errors = response.data.errors
            this.$store.dispatch('app/displayError', 'Registration error.')
          } else {
            this.$router.push({path: '/register/success'})
          }
        })
        .catch(() => {
          this.loading = false
          this.$store.dispatch('app/displayError', 'Registration error.')
        });
    },

    getFieldErrors(field) {
      return this.errors[field]
    },

    /**
     * Password validates.
     *
     * @returns {boolean}
     */
    passwordValidates() {
      if (this.password.length >= 6) {
        return true;
      }
    },

    /**
     * Form validates.
     *
     * @returns {boolean}
     */
    formValidates() {
      if (this.username.length && this.email.length && this.passwordValidates()) {
        return true;
      }

      return false;
    },
  }
}
</script>

