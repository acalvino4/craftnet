<template>
    <site-pane>
        <page-header>
            <div>
                <h1 class="mb-0">Create a Craft Console account</h1>
            </div>
        </page-header>

        <form method="post" accept-charset="UTF-8" @submit.prevent="submit()"
              ref="registerform">
            <field :vertical="true" label="Username" label-for="username">
                <textbox id="username" v-model="username"
                         :errors="getFieldErrors('username')"/>
            </field>
            <field :vertical="true" label="Email" label-for="email">
                <textbox type="email" id="email" v-model="email"
                         :errors="getFieldErrors('email')"/>
            </field>
            <field :vertical="true" label="Password" label-for="password">
                <textbox type="password" id="password" v-model="password"
                         :errors="getFieldErrors('password')"/>
            </field>
            <div class="mt-4">
                <btn kind="primary" type="submit" :loading="loading"
                     :disabled="!formValidates() || loading" block large>
                    Register
                </btn>
            </div>

            <div class="mt-3 text-sm">
                <p>Already have an account?
                    <router-link to="/login">Sign in to your account
                    </router-link>
                    .
                </p>
            </div>
        </form>
    </site-pane>
</template>

<script>
import usersApi from '../../api/users'
import helpers from '../../mixins/helpers.js'
import FormDataHelper from '../../helpers/form-data.js'
import SitePane from '@/console/js/components/site/SitePane';
import PageHeader from '@/console/js/components/PageHeader';

export default {
    components: {PageHeader, SitePane},
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

