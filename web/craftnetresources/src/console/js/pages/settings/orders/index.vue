<template>
  <component :is="computedComponent"></component>
</template>

<script>
import {mapGetters} from 'vuex';
import UserIndex from './_user';
import OrgIndex from './_org';

export default {
  computed: {
    ...mapGetters({
      currentOrganization: 'organizations/currentOrganization'
    }),

    computedComponent() {
      if (this.currentOrganization) {
        return OrgIndex
      }

      return UserIndex
    }
  },
}
</script>