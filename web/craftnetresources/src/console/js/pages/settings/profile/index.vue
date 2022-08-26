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

    if (this.currentOrganization && this.$route.params.orgSlug !== this.currentOrganization.slug) {
      // Redirect to the right org profile if the org slug is different than the current org slug.
      this.$router.push({
        name: 'OrgProfile',
        params: {
          orgSlug: this.currentOrganization.slug,
        },
      })
    } else if (!this.currentOrganization && this.$route.params.orgSlug) {
      // Redirect to the user profile if the org slug is provided but there is no current org.
      this.$router.push({
        name: 'UserProfile',
      })
    }
  }
}
</script>
