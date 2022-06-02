<template>
    <component :is="computedComponent"></component>
</template>

<script>
import {mapState} from 'vuex'
import PersonalIndex from './_personal'
import OrganizationIndex from './_organization'

export default {
    components: {
        PersonalIndex,
        OrganizationIndex,
    },
    computed: {
        ...mapState({
            currentOrganization: state => state.organizations.currentOrganization,
        }),

        computedComponent() {
            if (this.currentOrganization) {
                return OrganizationIndex
            }

            return PersonalIndex
        }
    },
}
</script>

