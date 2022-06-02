<template>
    <div>
        <page-header>
            <h1>Organizations</h1>
            <div>
                <btn kind="primary" to="/settings/organizations/new">New
                    organization
                </btn>
            </div>
        </page-header>

        <div class="space-y-6">
            <pane :padded="false">
                <div>
                    <div
                        v-for="(organization, organizationKey) in organizations"
                        :key="organizationKey">
                        <div class="flex items-center justify-between px-6 py-4"
                             :class="[{
                            'border-t': organizationKey !== 0
                        }]">
                            <div class="flex items-center">
                                <div class="mr-4">
                                    <div
                                        class="w-12 h-12 bg-gray-200 rounded-md overflow-hidden">
                                        <img
                                            :src="staticImageUrl('avatars/' + organization.avatar)"/>
                                    </div>
                                </div>
                                <div class="font-medium">
                                    {{ organization.name }}
                                </div>
                            </div>
                            <div>
                                <btn @click="leaveOrganization">Leave</btn>
                            </div>
                        </div>
                    </div>
                </div>
            </pane>


            <pane class="border border-red-500 mb-3">
                <template v-slot:header>
                    <h2 class="mb-0 text-red-600">
                        Danger Zone</h2>
                </template>

                <convert-account-to-organization></convert-account-to-organization>
            </pane>
        </div>
    </div>
</template>

<script>
import {mapActions, mapState} from 'vuex';
import helpers from '@/console/js/mixins/helpers.js';
import PageHeader from '@/console/js/components/PageHeader'
import ConvertAccountToOrganization from '@/console/js/components/ConvertAccountToOrganization';

export default {
    mixins: [helpers],

    components: {
        ConvertAccountToOrganization,
        PageHeader,
    },

    computed: {
        ...mapState({
            organizations: state => state.organizations.organizations,
        }),
    },

    methods: {
        ...mapActions({
            leaveOrganization: 'organizations/leaveOrganization',
        }),
    }
}
</script>