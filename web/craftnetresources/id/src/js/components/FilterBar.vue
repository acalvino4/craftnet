<template>
  <div class="filter-bar ui basic segment grid">
    <div class="ui form">
      <div class="inline flex items-center">
        <input
          class="flex-1"
          type="text"
          v-model="filterText"
          @input="onFilterTextInput"
          :placeholder="placeholder">
      </div>
    </div>
  </div>
</template>

<script>
import {debounce} from 'debounce'

export default {
  props: ['placeholder'],

  data() {
    return {
      filterText: ''
    }
  },

  methods: {
    onFilterTextInput() {
      this.$emit('filter-set', this.filterText)
    },
  },

  mounted() {
    this.onFilterTextInput = debounce(this.onFilterTextInput, 1000)
  }
}
</script>
