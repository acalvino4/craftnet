<template>
  <modal-headless
    :isOpen="showModal"
    @close="$emit('close')">
    <div class="w-full">
      <div class="-mt-2 flex items-center border-b pb-4">
        <icon
          icon="search"
          class="text-gray-400 w-6 h-6" />

        <input
          ref="searchInput"
          type="text"
          class="bg-white dark:bg-gray-800 mx-2 border-0 outline-none focus:ring-0 flex-1 rounded-md"
          placeholder="Search Craft Console" />

        <button
          class="border dark:border-gray-600 bg-gray-50 dark:bg-gray-700 rounded-md px-2 py-0.5 text-gray-600 dark:text-gray-400 text-sm"
          @click="$emit('close')">esc
        </button>
      </div>

      <div class="flex-1 mt-6 overflow-auto">
        <search-results
          @selectResult="$emit('close')"></search-results>
      </div>
    </div>
  </modal-headless>
</template>

<script>
import SearchResults from '@/console/js/components/SearchResults';
import ModalHeadless from '@/console/js/components/ModalHeadless';

export default {
  components: {
    ModalHeadless,
    SearchResults
  },

  props: ['showModal'],

  watch: {
    showModal(newVal) {
      if (newVal) {
        this.$nextTick(() => {
          this.$refs.searchInput.focus()
        })
      }
    }
  },
}
</script>

<style>
@media (min-width: 1024px) {
  .search-container {
    padding: 12vh;
  }
}
</style>