<template>
  <div>
    <template v-if="currentOrganization">
      <organization-profile />
    </template>
    <template v-else>
      <user-profile />
    </template>
  </div>
</template>

<script>
import {mapState, mapGetters} from 'vuex'
import OrganizationProfile from './_org';
import UserProfile from './_user';

export default {
  components: {
    OrganizationProfile,
    UserProfile,
  },
  computed: {
    ...mapState({
      user: state => state.account.user,
    }),

    ...mapGetters({
      currentOrganization: 'organizations/currentOrganization',
    }),
  },

  mounted() {
    this.userDraft = JSON.parse(JSON.stringify(this.user))
  }
}
</script>
