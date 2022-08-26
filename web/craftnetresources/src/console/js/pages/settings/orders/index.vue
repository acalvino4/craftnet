<template>
  <component :is="computedComponent"></component>
</template>

<script>
import {mapGetters} from 'vuex';
import PersonalIndex from './_personal';
import OrganizationIndex from './_organization';

export default {
  computed: {
    ...mapGetters({
      currentOrganization: 'organizations/currentOrganization'
    }),

    computedComponent() {
      if (this.currentOrganization) {
        return OrganizationIndex
      }

      return PersonalIndex
    }
  },

  mounted() {
    if (this.currentOrganization && this.$route.params.orgSlug !== this.currentOrganization.slug) {
      // Redirect to the right org profile if the org slug is different than the current org slug.
      this.$router.push({
        name: 'OrgOrders',
        params: {
          orgSlug: this.currentOrganization.slug,
        },
      })
    } else if (!this.currentOrganization && this.$route.params.orgSlug) {
      // Redirect to the user profile if the org slug is provided but there is no current org.
      this.$router.push({
        name: 'UserOrders',
      })
    }
  }
}
</script>