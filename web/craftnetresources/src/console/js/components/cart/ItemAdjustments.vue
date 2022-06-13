<template>
  <div
    v-if="item.lineItem.adjustments.filter(lineItemAdustment => lineItemAdustment.sourceSnapshot.type != 'extendedUpdates').length"
    class="mt-6 border-t pt-6">
    <div class="font-bold">
      Adjustments
    </div>
    <template
      v-for="(adjustment, adjustmentKey) in item.lineItem.adjustments.filter(lineItemAdustment => lineItemAdustment.sourceSnapshot.type != 'extendedUpdates')"
      :key="itemKey + 'adjustment-' + adjustmentKey">
      <div
        class="mt-2 bg-gray-100 dark:bg-gray-800 rounded py-2 px-3 flex justify-between">
        <div>
          {{ adjustment.name }}
        </div>
        <div class="text-right font-bold">
          {{ $filters.currency(adjustment.amount) }}
        </div>
      </div>
    </template>
  </div>
</template>

<script>
export default {
  props: {
    item: Object,
    itemKey: Number,
  }
}
</script>