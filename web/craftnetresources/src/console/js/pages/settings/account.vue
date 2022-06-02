<template>
    <form v-if="userDraft" @submit.prevent="save()">
        <page-header>
            <h1>Account</h1>
            <div>
                <btn kind="primary" type="submit" :disabled="loading"
                     :loading="loading">Save
                </btn>
            </div>
        </page-header>

        <div class="space-y-6">
            <pane>
                <template v-slot:header>
                    <h2>Username</h2>
                </template>

                <field :first="true" label-for="username" label="Username"
                       :errors="errors.username">
                    <textbox id="username" v-model="userDraft.username"
                             :invalid="!!errors.username"/>
                </field>
            </pane>

            <pane>
                <template v-slot:header>
                    <h2>Email &amp; password</h2>
                </template>

                <field :first="true" label-for="password"
                       label="Current Password"
                       :errors="errors.currentPassword">
                    <textbox id="password" type="password" v-model="password"
                             :invalid="!!errors.currentPassword"/>
                </field>

                <field label-for="email" label="Email" :errors="errors.email">
                    <textbox id="email" v-model="userDraft.email"
                             :invalid="!!errors.email"/>
                </field>

                <field label-for="newPassword" label="New Password"
                       :errors="errors.newPassword">
                    <textbox id="newPassword" type="password"
                             v-model="newPassword"
                             :invalid="!!errors.newPassword"/>
                </field>
            </pane>
        </div>
    </form>
</template>

<script>
import {mapState, mapGetters} from 'vuex'
import PageHeader from '@/console/js/components/PageHeader'

export default {
    components: {
        PageHeader,
    },

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
