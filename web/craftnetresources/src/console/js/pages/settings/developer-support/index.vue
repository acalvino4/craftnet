<template>
  <component :is="computedComponent"></component>
</template>

<script>
import {mapGetters} from 'vuex'
import PersonalIndex from './_personal'
import OrganizationIndex from './_organization'

export default {
  components: {
    PersonalIndex,
    OrganizationIndex,
  },
  computed: {
    ...mapGetters({
      currentOrganization: 'organizations/currentOrganization'
    }),

    computedComponent() {
      // TODO: Enable developer support for organizations
      // if (this.currentOrganization) {
      //   return OrganizationIndex
      // }

      return PersonalIndex
    }
  },
}
</script>

