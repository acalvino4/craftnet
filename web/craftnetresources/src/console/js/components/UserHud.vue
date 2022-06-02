<template>
    <Menu as="div" class="">
        <MenuButton
            class="block header-toggle px-3 py-2 flex items-center justify-center hover:bg-black dark:hover:bg-white hover:bg-opacity-5 rounded-md">
            <icon icon="user" class="w-4 h-4 text-gray-500"/>
        </MenuButton>
        <MenuItems
            class="absolute z-10 -right-2 mt-2 w-56 origin-top-right bg-white dark:bg-gray-800 divide-y divide-gray-100 rounded-md shadow-lg ring-1 ring-black dark:ring-white ring-opacity-10 dark:ring-opacity-20 focus:outline-none">
            <div class="px-2 py-1">
                <MenuItem class="truncate" v-slot="{active}">
                    <button
                        class="block w-full text-left rounded text-sm my-1 px-3 py-2 leading-5 border border-transparent text-gray-800 dark:text-gray-200 hover:text-gray-800 dark:text-gray-200 hover:border-interactive-nav-active-background hover:bg-gray-200 dark:bg-gray-700 hover:no-underline"
                        :class="[{
                                    'bg-gray-200 dark:bg-gray-700': active,
                                }]"
                        :title="user.email"
                        @click="$store.commit('organizations/updateCurrentOrganization', null);$router.push({path: '/settings/account'})">
                        {{ user.email }}
                    </button>
                </MenuItem>

                <hr class="my-2 mx-3 border-separator"/>

                <MenuItem class="truncate" v-slot="{active}">
                    <a href="/logout"
                       class="block rounded text-sm my-1 px-3 py-2 leading-5 border border-transparent text-gray-800 dark:text-gray-200 hover:text-gray-800 dark:text-gray-200 hover:border-interactive-nav-active-background hover:bg-gray-200 dark:bg-gray-700 hover:no-underline"
                       :class="[{
                            'bg-gray-200 dark:bg-gray-700': active,
                        }]">Logout</a>
                </MenuItem>
            </div>
        </MenuItems>
    </Menu>

    <div class="absolute right-0 w-56 z-10" :class="{hidden: !showingUserMenu}">
        <div class="bg-primary shadow-lg rounded-md mt-2">
            <div class="shadow-xs rounded-md px-2 py-1">
                <div :title="user.email"
                     class="truncate text-sm my-1 px-3 py-2">
                    {{ user.email }}
                </div>

                <hr class="my-2 mx-3 border-separator"/>

                <ul>
                    <li>
                    </li>
                    <li>
                    </li>
                </ul>

                <hr class="my-2 mx-3 border-separator"/>

                <ul>
                    <li>
                        <a href="/logout"
                           class="block rounded text-sm my-1 px-3 py-2 leading-5 border border-transparent text-gray-800 dark:text-gray-200 hover:text-gray-800 dark:text-gray-200 hover:border-interactive-nav-active-background hover:bg-gray-200 dark:bg-gray-700 hover:no-underline">Logout</a>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</template>

<script>
import {Menu, MenuButton, MenuItems, MenuItem} from '@headlessui/vue'
import {mapState} from 'vuex';

export default {
    components: {
        Menu,
        MenuButton,
        MenuItems,
        MenuItem,
    },
    data() {
        return {
            showingUserMenu: false,
        }
    },

    computed: {
        ...mapState({
            user: state => state.account.user,
        }),
    },

    methods: {
        /**
         * User menu toggle.
         */
        userMenuToggle() {
            this.showingUserMenu = !this.showingUserMenu
        },

        close() {
            this.showingUserMenu = !this.showingUserMenu
        }
    }
}
</script>