<template>
  <div class="bg-gray-50/50 border dark:bg-gray-800/50 border-gray-300 dark:border-gray-700 rounded-md p-6 relative">
    <template v-if="paymentMethod.isPrimary">
      <div class="absolute top-6 right-6">
        <badge type="success">Primary</badge>
      </div>
    </template>

    <div>
      <div class="uppercase font-medium text-gray-600">
        {{ paymentMethod.card.brand }}
      </div>

      <div class="mt-6 font-mono">
        **** **** **** {{ paymentMethod.card.last4 }}
      </div>

      <div class="text-sm text-gray-500">
        {{ paymentMethod.card.exp_month }}/{{ paymentMethod.card.exp_year }}
      </div>
    </div>


    <div class="mt-4 space-x-4">
      <a href="#" @click.prevent="$emit('edit')">Edit</a>
      <template v-if="!paymentMethod.isPrimary">
        <a href="#" @click.prevent="$emit('makePrimary')">Make primary</a>
      </template>
    </div>
  </div>
</template>

<script>
export default {
  props: {
    paymentMethod: {
      type: Object,
      required: true,
    },
  }
}
</script>