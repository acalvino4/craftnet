<template>
  <div class="border border-gray-200 dark:border-gray-700 rounded-md p-6 flex flex-col">
    <div class="flex-1">
      <div class="text-xs font-mono text-gray-500">#{{address.id}}</div>

      <div class="mt-2 space-x-2">
        <badge type="info">{{address.title}}</badge>

        <template v-if="address.isPrimary">
          <badge>Primary</badge>
        </template>
      </div>
      <ul class="mt-4">
        <template v-for="(showingField, showingFieldKey) in showingFields" :key="showingFieldKey">
          <li>{{address[showingField]}}</li>
        </template>
      </ul>
    </div>

    <div class="mt-4 space-x-4">
      <a href="#" @click.prevent="$emit('edit')">Edit</a>
      <a href="#" @click.prevent="$emit('remove')">Remove</a>
    </div>
  </div>
</template>

<script>
export default {
  data() {
    return {
      showingFields: [
        'fullName',
        'countryCode',
        'postalCode',
        'addressLine1',
        'addressLine2',
        'organization',
        'organizationTaxId',
      ]
    }
  },

  props: {
    address: {
      type: Object,
      required: true,
    },
  }
}
</script>