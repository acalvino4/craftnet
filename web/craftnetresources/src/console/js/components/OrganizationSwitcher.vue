<template>
    <div v-if="user" class="header-left flex">
        <div class="inline-block flex-1">
            <Menu as="div" class="header-brand relative">
                <MenuButton
                    class="flex w-full items-center hover:no-underline font-bold text-gray-800 dark:text-gray-200 px-5 py-3">
                        <span class="rounded overflow-hidden">
                            <template v-if="currentOrganization">
                                <template v-if="orgAvatarUrl">
                                    <img class="w-7 h-7 bg-teal-500"
                                         :src="orgAvatarUrl"/>
                                </template>
                            </template>
                            <template v-else>
                                <div
                                    class="w-7 h-7 bg-gray-100 flex items-center justify-center">
                                    <template v-if="user.photoUrl">
                                        <img :src="user.photoUrl"/>
                                    </template>
                                    <template v-else>
                                        <icon icon="user"
                                              class="w-3 h-3 text-gray-500"/>
                                    </template>
                                </div>
                            </template>
                        </span>

                    <div v-if="orgName" class="ml-2 inline-block">
                        {{ orgName }}
                    </div>

                    <icon icon="chevron-down"
                          class="w-4 h-4 ml-1 mt-0.5 text-gray-700 dark:text-gray-400"/>
                </MenuButton>

                <MenuItems
                    class="absolute z-10 left-4 w-56 origin-top-right bg-white dark:bg-gray-700 divide-y divide-gray-100 rounded-md shadow-lg ring-1 ring-black ring-opacity-10 dark:ring-white dark:ring-opacity-20 focus:outline-none">
                    <div class="px-2 py-1">
                      <MenuItem v-if="user" v-slot="{active}">
                        <organization-switcher-menu-item
                          :active="active"
                          :checked="!currentOrganization"
                          @click="selectOrganization(null)"
                        >
                          <div class="flex items-center">
                            <div
                              class="w-7 h-7 bg-gray-100 rounded overflow-hidden mr-2 flex items-center justify-center">
                              <template v-if="user.photoUrl">
                                <img :src="user.photoUrl"/>
                              </template>
                              <template v-else>
                                <icon icon="user"
                                      class="w-3 h-3 text-gray-500"/>
                              </template>
                            </div>

                            <template
                              v-if="user.firstName || user.lastName">
                              {{ user.firstName }} {{ user.lastName }}
                            </template>
                            <template v-else-if="user.developerName">
                              {{ user.developerName }}
                            </template>
                          </div>
                        </organization-switcher-menu-item>
                      </MenuItem>

                        <template
                            v-for="(organization, organizationKey) in organizations"
                            :key="'org-hud-item-' + organizationKey">
                            <MenuItem v-slot="{active}">
                                <organization-switcher-menu-item
                                    :active="active"
                                    :checked="currentOrganization && currentOrganization.id === organization.id"
                                    @click="selectOrganization(organization)"
                                >
                                    <div class="flex items-center min-w-0">
                                        <img class="w-7 h-7 rounded mr-2"
                                             :src="staticImageUrl('avatars/' + organization.avatar)"/>
                                        <div class="truncate">
                                            {{ organization.name }}
                                        </div>
                                    </div>
                                </organization-switcher-menu-item>
                            </MenuItem>
                        </template>

                        <hr class="my-2 mx-3 border-t dark:border-gray-600"/>

                        <MenuItem class="truncate" v-slot="{active}">
                            <organization-switcher-menu-item
                                :active="active"
                                @click="newOrganization"
                            >
                                <icon icon="plus"
                                      class="w-5 h-5 inline-block mr-2"/>
                                Add an organization
                            </organization-switcher-menu-item>
                        </MenuItem>
                    </div>
                </MenuItems>
            </Menu>
        </div>
    </div>
</template>

<script>
import {Menu, MenuButton, MenuItems, MenuItem} from '@headlessui/vue'
import {mapState} from 'vuex';
import helpers from '@/console/js/mixins/helpers.js';
import OrganizationSwitcherMenuItem from './OrganizationSwitcherMenuItem';

export default {
    mixins: [helpers],

    components: {
      OrganizationSwitcherMenuItem,
        Menu,
        MenuButton,
        MenuItems,
        MenuItem,
    },

    computed: {
        ...mapState({
            organizations: state => state.organizations.organizations,
            currentOrganization: state => state.organizations.currentOrganization,
            user: state => state.account.user,
        }),

        orgAvatarUrl() {
            if (this.currentOrganization) {
                return this.staticImageUrl('avatars/' + this.currentOrganization.avatar)
            }

            if (this.user && this.user.photoUrl) {
                return this.user.photoUrl
            }

            return null
        },

        orgName() {
            if (this.currentOrganization) {
                return this.currentOrganization.name
            }

            if (this.user && (this.user.firstName || this.user.lastName)) {
                let name = ''

                if (this.user.firstName) {
                    name += this.user.firstName
                }

                if (this.user.firstName && this.user.lastName) {
                    name += ' '
                }

                if (this.user.lastName) {
                    name += this.user.lastName
                }

                return name
            }

            if (this.user && this.user.developerName) {
                return this.user.developerName
            }

            return null
        }
    },

    methods: {
        /**
         * Select an organization.
         */
        selectOrganization(organization) {
            this.$store.commit('organizations/updateCurrentOrganization', organization)

            if (!organization && this.$route.path === '/settings/members') {
                this.$router.push('/settings/profile')
            }

            if (organization && this.$route.path === '/settings/organizations') {
                this.$router.push('/settings/profile')
            }
        },

        newOrganization() {
            this.$store.commit('organizations/updateCurrentOrganization', null)
            this.$router.push({path: '/settings/organizations/new'})
        }
    }
}
</script>