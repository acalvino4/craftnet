<template>
  <div>
    <page-header>
      <h1>Organizations</h1>
      <div>
        <btn
          kind="primary"
          to="/settings/organizations/new">New
          organization
        </btn>
      </div>
    </page-header>

    <div class="space-y-6">
      <template v-if="organizations.length > 0">
        <pane :padded="false">
          <div>
            <div
              v-for="(organization, organizationKey) in organizations"
              :key="organizationKey">
              <div
                class="flex items-center justify-between px-6 py-4"
                :class="[{
                  'border-t': organizationKey !== 0
                }]">
                <div class="flex items-center">
                  <div class="mr-4">
                    <div
                      :class="{
                        'w-12 h-12 rounded-md overflow-hidden': true,
                        'bg-gray-100 dark:bg-gray-400': !organization.avatar,
                      }"
                    >
                      <template v-if="organization.avatar">
                        <img
                          :src="staticImageUrl('avatars/' + organization.avatar)" />
                      </template>
                    </div>
                  </div>
                  <div class="font-medium">
                    {{ organization.displayName }}
                  </div>
                </div>
                <div>
                  <btn @click="leaveOrganization">Leave</btn>
                </div>
              </div>
            </div>
          </div>
        </pane>
      </template>
      <template v-else>
        <pane>
          <div class="flex gap-6 max-w-lg mx-auto px-12 py-24">
            <div>
              <icon
                class="text-blue-500 w-16 h-16"
                icon="collection"
              />
            </div>

            <div>
              <h2>No organizations</h2>
              <p class="text-gray-600">You are not part of any organizations, create or join an organization.</p>
            </div>
          </div>
        </pane>
      </template>



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