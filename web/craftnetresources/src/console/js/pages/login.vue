<template>
    <site-pane>
        <page-header>
            <h1>Sign into Craft Console</h1>
        </page-header>

        <form id="login-form" method="post" accept-charset="UTF-8"
              @submit.prevent="submit()">
            <input type="hidden" :name="csrfTokenName" :value="csrfTokenValue">
            <input type="hidden" name="action" value="users/login">

            <field label-for="loginName" label="Username or email" vertical
                   first>
                <textbox id="loginName" v-model="loginName"
                         ref="loginNameField"/>
            </field>

            <field label-for="password" label="Password" vertical>
                <textbox id="password" type="password" v-model="password"
                         ref="passwordField"/>
            </field>

            <div class="flex items-center justify-between mt-6 mb-4">
                <div>
                    <checkbox class="my-0" v-model="rememberMe"
                              label="Remember me"></checkbox>
                </div>

                <div>
                    <router-link class="text-sm" to="/forgot-password">Forgot
                        your password?
                    </router-link>
                </div>
            </div>

            <btn kind="primary" type="submit" :loading="loading"
                 :disabled="loading" block large>Login
            </btn>

            <div class="mt-3 text-sm">
                <p>Don’t have an account yet?
                    <router-link to="/register">Create yours now</router-link>.
                </p>
            </div>
        </form>
    </site-pane>
</template>

<script>
/* global Craft */

import {mapState} from 'vuex'
import usersApi from '../api/users'
import helpers from '../mixins/helpers.js'
import FormDataHelper from '../helpers/form-data.js'
import PageHeader from '@/console/js/components/PageHeader'
import SitePane from '@/console/js/components/site/SitePane';

export default {
    mixins: [helpers],

    components: {
        SitePane,
        PageHeader,
    },

    data() {
        return {
            loading: false,
            loginName: '',
            password: '',
            rememberMe: false,
        };
    },

    computed: {
        ...mapState({
            user: state => state.account.user,
        }),

        csrfTokenName() {
            return Craft.csrfTokenName;
        },

        csrfTokenValue() {
            return Craft.csrfTokenValue;
        },

        rememberedUsername() {
            return window.rememberedUsername
        }
    },

    methods: {
        submit() {
            if (this.loading) {
                return false
            }

            if (!this.formValidates()) {
                this.$store.dispatch('app/displayError', 'Couldn’t login.')
                return false
            }

            this.loading = true


            // Send login request

            let formData = new FormData()

            FormDataHelper.append(formData, 'loginName', this.loginName)
            FormDataHelper.append(formData, 'password', this.password)
            FormDataHelper.append(formData, 'rememberMe', (this.rememberMe ? '1' : '0'))

            usersApi.login(formData)
                .then((response) => {
                    if (response.data.error) {
                        this.loading = false
                        this.$store.dispatch('app/displayError', response.data.error)
                        return
                    }

                    // Set `remainingSessionTime` to something different than 0 to give the auth manager a chance to get the real remaining session time
                    // Todo: Take Craft’s userSessionDuration config into account
                    Craft.remainingSessionTime = 3600

                    if (response.data.returnUrl) {
                        window.location.replace(response.data.returnUrl)

                        // Todo: Refresh CSRF token after login
                        // Returns a “Unable to verify your data submission.” error because CSRF token needs to be refreshed after login.
                        //
                        // if (response.data.returnUrl === window.craftIdUrl + '/') {
                        //     this.$store.dispatch('account/getAccount')
                        //         .then(() => {
                        //             this.$router.push({path: '/'})
                        //         })
                        // }  else {
                        //     window.location.replace(response.data.returnUrl)
                        // }

                        return
                    } else {
                        this.loading = false
                        this.$store.dispatch('app/displayError', 'Couldn’t login.')
                    }
                })
                .catch(() => {
                    this.loading = false
                    this.$store.dispatch('app/displayError', 'Couldn’t login.')
                });
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
            if (this.loginName.length && this.passwordValidates()) {
                return true;
            }

            return false;
        },
    },

    mounted() {
        if (this.$route.query.activated) {
            this.$store.dispatch('app/displayNotice', 'Email verified.')
        }

        if (this.user) {
            this.$router.push({path: '/'})
        } else {
            if (this.rememberedUsername) {
                this.loginName = this.rememberedUsername;
            }

            if (this.loginName.length === 0) {
                this.$refs.loginNameField.$refs.input.focus();
            } else {
                this.$refs.passwordField.$refs.input.focus();
            }
        }
    }
}
</script>
