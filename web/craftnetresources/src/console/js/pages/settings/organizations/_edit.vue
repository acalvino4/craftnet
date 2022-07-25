<template>
  <div>
    <form @submit.prevent="save()">
      <page-header>
        <h1>New organization</h1>

        <div class="flex space-x-2">
          <btn to="/settings/organizations">Cancel</btn>
          <btn type="submit" kind="primary">Save</btn>
        </div>
      </page-header>

      <pane>
        <field
          label-for="name"
          label="Organization name"
          :errors="errors.displayName"
          :first="true">
          <textbox
            id="name"
            v-model="organization.displayName"
            :invalid="!!errors.displayName" />
        </field>
        <field
          label-for="slug"
          label="Slug"
          :errors="errors.slug">
          <textbox
            id="slug"
            v-model="organization.slug"
            :invalid="!!errors.slug" />
        </field>
        <field
          label-for="url"
          label="Website URL"
          :errors="errors.url">
          <textbox
            id="url"
            v-model="organization.url"
            :invalid="!!errors.url" />
        </field>
        <field
          label-for="location"
          label="Location"
          :errors="errors.location">
          <textbox
            id="location"
            v-model="organization.location"
            :invalid="!!errors.location" />
        </field>
        <field
          label-for="photo"
          label="Photo"
          :errors="errors.photo">
          <textbox
            id="photo"
            v-model="organization.photo"
            :invalid="!!errors.photo" />
        </field>
      </pane>
    </form>
  </div>
</template>

<script>
import PageHeader from '@/console/js/components/PageHeader';

export default {
  components: {PageHeader},
  data() {
    return {
      organization: {},
      errors: {}
    }
  },

  methods: {
    save() {
      this.$store.dispatch('organizations/saveOrganization', this.organization)
    }
  }
}
</script>