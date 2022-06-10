<template>
    <div>
        <!-- Root modal -->
        <TransitionRoot appear :show="showingRootModal" as="template">
            <Dialog as="div" @close="fake">
                <div class="fixed inset-0 z-10 overflow-y-auto">
                    <div class="min-h-screen px-4 text-center">
                        <TransitionChild
                            as="template"
                            enter="duration-300 ease-out"
                            enter-from="opacity-0"
                            enter-to="opacity-100"
                            leave="duration-200 ease-in"
                            leave-from="opacity-100"
                            leave-to="opacity-0"
                        >
                            <DialogOverlay
                                class="fixed inset-0 bg-black bg-opacity-75"/>
                        </TransitionChild>

                        <span class="inline-block h-screen align-middle"
                              aria-hidden="true">&#8203;</span>

                        <TransitionChild
                            as="template"
                            enter="duration-300 ease-out"
                            enter-from="opacity-0 scale-95"
                            enter-to="opacity-100 scale-100"
                            leave="duration-200 ease-in"
                            leave-from="opacity-100 scale-100"
                            leave-to="opacity-0 scale-95"
                        >
                            <div
                                class="space-x-3 absolute top-0 left-0 transform -translate-y-full">

                                <button @click="fake">
                                    Open Logout Warning
                                </button>
                            </div>
                        </TransitionChild>
                    </div>
                </div>

                <!-- Logout warning modal -->
                <TransitionRoot
                    appear
                    :show="showingLogoutWarningModal"
                    as="template"
                    @after-leave="onAfterLeaveLogoutWarningModal"
                >
                    <Dialog as="div" @close="fake">
                        <div class="fixed inset-0 z-10 overflow-y-auto">
                            <div class="min-h-screen px-4 text-center">
                                <DialogOverlay class="fixed inset-0"/>

                                <span class="inline-block h-screen align-middle"
                                      aria-hidden="true">&#8203;</span>

                                <TransitionChild
                                    as="template"
                                    enter="duration-300 ease-out"
                                    enter-from="opacity-0 scale-95"
                                    enter-to="opacity-100 scale-100"
                                    leave="duration-200 ease-in"
                                    leave-from="opacity-100 scale-100"
                                    leave-to="opacity-0 scale-95"
                                    @after-enter="onAfterEnterLogoutWarningModal"
                                >
                                    <div
                                        class="inline-block w-full max-w-md p-6 my-8 overflow-hidden text-left align-middle transition-all transform bg-primary shadow-xl rounded-2xl"
                                    >
                                        <div>
                                            <div class="flex items-center">
                                                <icon icon="exclamation"
                                                      class="w-12 h-12 text-gray-400 mr-2"/>
                                                <div>
                                                    Your session will expire in
                                                    {{
                                                        humanizedRemainingSessionTime
                                                    }}.
                                                </div>
                                            </div>

                                            <div
                                                class="mt-6 space-x-reverse space-x-2 flex flex-row-reverse justify-start">
                                                <btn kind="primary"
                                                     ref="renewSessionBtn"
                                                     @click="renewSession">Keep
                                                    me logged in
                                                </btn>
                                                <btn @click="logout">Logout
                                                    now
                                                </btn>
                                            </div>
                                        </div>
                                    </div>
                                </TransitionChild>
                            </div>
                        </div>
                    </Dialog>
                </TransitionRoot>

                <!-- login modal -->
                <TransitionRoot
                    appear
                    :show="showingLoginModal"
                    as="template"

                    @after-leave="onAfterLeaveLoginModal"
                >
                    <Dialog as="div">
                        <div class="fixed inset-0 z-10 overflow-y-auto">
                            <div class="min-h-screen px-4 text-center">
                                <TransitionChild
                                    as="template"
                                    enter="duration-300 ease-out"
                                    enter-from="opacity-0"
                                    enter-to="opacity-100"
                                    leave="duration-200 ease-in"
                                    leave-from="opacity-100"
                                    leave-to="opacity-0"
                                >
                                    <DialogOverlay class="fixed inset-0"/>
                                </TransitionChild>

                                <span class="inline-block h-screen align-middle"
                                      aria-hidden="true">&#8203;</span>

                                <TransitionChild
                                    as="template"
                                    enter="duration-300 ease-out"
                                    enter-from="opacity-0 scale-95"
                                    enter-to="opacity-100 scale-100"
                                    leave="duration-200 ease-in"
                                    leave-from="opacity-100 scale-100"
                                    leave-to="opacity-0 scale-95"
                                    @after-enter="onAfterEnterLoginModal"
                                >
                                    <div
                                        class="inline-block w-full max-w-md p-6 my-8 overflow-hidden text-left align-middle transition-all transform bg-primary shadow-xl rounded-2xl"
                                    >
                                        <div class="flex space-x-4">
                                            <icon icon="exclamation"
                                                  class="w-12 h-12 text-gray-400"/>
                                            <form class="flex-1"
                                                  @submit.prevent="login">
                                                <h6 class="font-bold">Your
                                                    session has ended</h6>
                                                <p class="text-sm">Enter your
                                                    password to log back in.</p>

                                                <div class="mt-4">
                                                    <div
                                                        class="flex items-center space-x-3">
                                                        <div class="flex-1">
                                                            <textbox
                                                                ref="passwordInput"
                                                                type="password"
                                                                placeholder="Password"
                                                                v-model="password"
                                                                :is-invalid="!!loginError"/>
                                                        </div>
                                                        <btn kind="primary"
                                                             type="submit">Login
                                                        </btn>
                                                        <spinner
                                                            :class="{'invisible': !passwordSpinner}"></spinner>
                                                    </div>
                                                </div>

                                                <div class="text-red"
                                                     v-if="loginError">
                                                    {{ loginError }}
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </TransitionChild>
                            </div>
                        </div>
                    </Dialog>
                </TransitionRoot>
            </Dialog>
        </TransitionRoot>
    </div>
</template>

<script>
/* global Craft */

import {mapState} from 'vuex'
import usersApi from '../api/users'
import FormDataHelper from '../helpers/form-data.js'
import IsMobileBrowser from './IsMobileBrowser.js';
import humanizeDuration from 'humanize-duration';
import {
    TransitionRoot,
    TransitionChild,
    Dialog,
    DialogOverlay,
} from '@headlessui/vue'

export default {
    components: {
        TransitionRoot,
        TransitionChild,
        Dialog,
        DialogOverlay,
    },

    mixins: [IsMobileBrowser],

    data() {
        return {
            showingLoginModal: false,
            showingLogoutWarningModal: false,
            logoutWarning: null,
            loginError: null,
            password: null,
            passwordValidates: false,
            passwordSpinner: false,
            minSafeSessionTime: 120,
            checkInterval: 60,
            remainingSessionTime: null,

            showingRootModal: false,
            pleaseOpenLoginModal: false,
            pleaseCheckRemainingSessionTime: false,
        }
    },

    computed: {
        ...mapState({
            user: state => state.account.user,
        }),

        humanizedRemainingSessionTime() {
            return humanizeDuration(this.remainingSessionTime * 1000);
        }
    },

    methods: {
        /**
         * Sets a timer for the next time to check the auth timeout.
         */
        setCheckRemainingSessionTimer(seconds) {
            if (this.checkRemainingSessionTimer) {
                clearTimeout(this.checkRemainingSessionTimer);
            }

            this.checkRemainingSessionTimer = setTimeout(function() {
                this.checkRemainingSessionTime();
            }.bind(this), seconds * 1000);
        },

        /**
         * Pings the server to see how many seconds are left on the current user session, and handles the response.
         */
        checkRemainingSessionTime(extendSession) {
            let config = {};

            if (!extendSession) {
                config.params = {
                    dontExtendSession: 1
                };
            }

            usersApi.getRemainingSessionTime(config)
                .then(response => {
                    if (typeof response.data.csrfTokenValue !== 'undefined' && typeof Craft.csrfTokenValue !== 'undefined') {
                        Craft.csrfTokenValue = response.data.csrfTokenValue;
                    }

                    this.updateRemainingSessionTime(response.data.timeout);
                    this.submitLoginIfLoggedOut = false;
                })
                .catch(() => {
                    this.updateRemainingSessionTime(-1);
                });
        },

        /**
         * Updates our record of the auth timeout, and handles it.
         */
        updateRemainingSessionTime(remainingSessionTime) {
            if (!this.user) {
                return false
            }

            this.remainingSessionTime = parseInt(remainingSessionTime)

            // Are we within the warning window?
            if (this.remainingSessionTime !== -1 && this.remainingSessionTime < this.minSafeSessionTime) {
                // Is there still time to renew the session?
                if (this.remainingSessionTime) {
                    if (!this.showingLogoutWarningModal) {
                        // Show the warning modal
                        this.showLogoutWarningModal();
                    }

                    // Will the session expire before the next checkup?
                    if (this.remainingSessionTime < this.checkInterval) {
                        if (this.showLoginModalTimer) {
                            clearTimeout(this.showLoginModalTimer);
                        }

                        this.showLoginModalTimer = setTimeout(function() {
                            this.showLoginModal();
                        }.bind(this), this.remainingSessionTime * 1000);
                    }
                } else {
                    if (this.showingLoginModal) {
                        if (this.submitLoginIfLoggedOut) {
                            this.submitLogin();
                        }
                    } else {
                        // Show the login modal
                        this.showLoginModal();
                    }
                }

                this.setCheckRemainingSessionTimer(this.checkInterval);
            } else {
                // Everything's good!
                this.hideLogoutWarningModal();
                this.hideLoginModal();

                // Will we be within the minSafeSessionTime before the next update?
                if (this.remainingSessionTime !== -1 && this.remainingSessionTime < (this.minSafeSessionTime + this.checkInterval)) {
                    this.setCheckRemainingSessionTimer(this.remainingSessionTime - this.minSafeSessionTime + 1);
                } else {
                    this.setCheckRemainingSessionTimer(this.checkInterval);
                }
            }
        },

        /**
         * Shows the logout warning modal.
         */
        showLogoutWarningModal() {
            if (this.showingLoginModal) {
                this.hideLoginModal(true);
            }

            this.$nextTick(() => {
                this.showingRootModal = true;
                this.showingLogoutWarningModal = true;
            });

            this.decrementLogoutWarningInterval = setInterval(function() {
                this.decrementLogoutWarning()
            }.bind(this), 1000);

        },

        /**
         * Updates the logout warning message indicating that the session is about to expire.
         */
        updateLogoutWarningMessage() {
            this.logoutWarning = `Your session will expire in ${this.humanizedRemainingSessionTime}`;
        },

        /**
         * Decrement logout warning.
         */
        decrementLogoutWarning() {
            if (this.remainingSessionTime > 0) {
                this.remainingSessionTime--;
                this.updateLogoutWarningMessage();
            }

            if (this.remainingSessionTime === 0) {
                clearInterval(this.decrementLogoutWarningInterval);
            }
        },

        /**
         * Hides the logout warning modal.
         */
        hideLogoutWarningModal(quick) {
            this.$nextTick(() => {
                this.showingLogoutWarningModal = false;

                if (!quick) {
                    this.showingRootModal = false;
                }
            });

            if (this.decrementLogoutWarningInterval) {
                clearInterval(this.decrementLogoutWarningInterval);
            }
        },

        /**
         * Shows the login modal.
         */
        showLoginModal() {
            if (this.showingLogoutWarningModal) {
                this.hideLogoutWarningModal(true);
                this.$nextTick(() => {
                    this.showingRootModal = true
                    this.pleaseOpenLoginModal = true
                })
            } else {
                this.showingRootModal = true
                this.showingLoginModal = true
            }
        },

        /**
         * Hides the login modal.
         */
        hideLoginModal(quick) {
            this.$nextTick(() => {
                this.showingLoginModal = false;

                if (!quick) {
                    this.showingRootModal = false
                }
            });
        },

        /**
         * Logout.
         */
        logout() {
            usersApi.logout()
                .then(() => {
                    document.location.href = '';
                })
        },

        /**
         * Renew session.
         */
        renewSession() {
            this.hideLogoutWarningModal();
            this.checkRemainingSessionTime(true);
        },

        /**
         * Validate password.
         */
        validatePassword() {
            if (this.password && this.password.length >= 6) {
                this.passwordValidates = true;
                return true;
            } else {
                this.passwordValidates = false;
                return false;
            }
        },

        /**
         * Login.
         */
        login() {
            if (this.validatePassword()) {
                this.passwordSpinner = true;

                this.clearLoginError();

                if (typeof Craft.csrfTokenValue !== 'undefined') {
                    // Check the auth status one last time before sending this off,
                    // in case the user has already logged back in from another window/tab
                    this.submitLoginIfLoggedOut = true;
                    this.checkRemainingSessionTime();
                } else {
                    this.submitLogin();
                }
            }
        },

        /**
         * Submit login.
         */
        submitLogin() {
            let formData = new FormData()

            FormDataHelper.append(formData, 'loginName', this.user.username)
            FormDataHelper.append(formData, 'password', this.password)

            usersApi.login(formData)
                .then(response => {
                    this.passwordSpinner = false;

                    if (response.data.success) {
                        this.hideLoginModal();
                        this.pleaseCheckRemainingSessionTime = true

                        // this.checkRemainingSessionTime();
                    } else {
                        this.showLoginError(response.data.error);

                        if (!this.isMobileBrowser(true)) {
                            this.$refs.passwordInput.focus();
                        }
                    }
                })
                .catch(() => {
                    this.passwordSpinner = false;
                    this.showLoginError();
                });
        },

        /**
         * Show login error.
         */
        showLoginError(error) {
            if (error === null || typeof error === 'undefined') {
                error = 'An unknown error occurred.';
            }

            this.loginError = error;
        },

        /**
         * Clear login error.
         */
        clearLoginError() {
            this.showLoginError('');
        },

        /**
         * On after enter login modal.
         */
        onAfterEnterLoginModal() {
            if (!this.isMobileBrowser(true)) {
                this.$refs.passwordInput.$el.querySelector('input').focus()
            }
        },

        /**
         * On after enter logout warning modal.
         */
        onAfterEnterLogoutWarningModal() {
            if (!this.isMobileBrowser(true)) {
                this.$refs.renewSessionBtn.$el.focus();
            }
        },

        /**
         * On after leave login modal.
         */
        onAfterLeaveLoginModal() {
            if (this.pleaseCheckRemainingSessionTime) {
                this.$nextTick(() => {
                    this.checkRemainingSessionTime();
                    this.pleaseCheckRemainingSessionTime = false
                })
            }

            this.password = '';
        },

        /**
         * On after leave logout warning modal.
         */
        onAfterLeaveLogoutWarningModal() {
            if (this.pleaseOpenLoginModal) {
                this.$nextTick(() => {
                    this.showingLoginModal = true
                    this.pleaseOpenLoginModal = false
                })
            }
        },

        fake() {
            return null
        },
    },

    watch: {
        /**
         * Validate password when the password value changes.
         */
        password(newVal) {
            this.validatePassword();

            return newVal;
        }
    },

    mounted() {
        // Let's get it started.
        this.updateRemainingSessionTime(Craft.remainingSessionTime);
    }
}
</script>

<style lang="scss">
.auth-manager-modal {
    z-index: 20;

    .modal-body {
        position: relative;
        padding-left: 72px;

        & > .icon {
            position: absolute;
            top: 0;
            left: 0;
            width: 46px;
            height: 46px;
            color: #b9bfc6;
        }
    }
}
</style>
