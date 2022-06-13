<template>
  <ul
    ref="searchResults"
    class="space-y-2">
    <li
      v-for="(result, resultKey) in results"
      :key="resultKey">
      <router-link
        :to="result.to"
        class="group flex justify-between items-center hover:no-underline block px-4 py-3 rounded-md"
        :class="{
                    'text-blue-500 bg-gray-50 hover:text-blue-500 dark:bg-gray-700': resultKey !== active,
                    'bg-blue-500 text-white hover:text-white': resultKey === active,
                    'hover:bg-blue-500': active === -1
                }"
        @mouseenter="active = resultKey"
      >
        {{ result.label }}

        <icon
          icon="arrow-turn-down-left"
          class="w-4 h-4"
          :class="{
                        'text-gray-500 group-hover:text-gray-500': resultKey !== active,
                        'text-white': resultKey === active,
                    }" />
      </router-link>
    </li>
  </ul>
</template>

<script>

// https://stackoverflow.com/questions/62520252/vue-js-keys-events-up-down-enter-to-control-selection

export default {
  data() {
    return {
      active: -1,
      results: [
        {
          to: "/licenses/cms",
          label: "Result 1",
        },
        {
          to: "/licenses/plugins",
          label: "Result 2",
        },
        {
          to: "/licenses/cms",
          label: "Result 3",
        },
        {
          to: "/licenses/cms",
          label: "Result 4",
        },
        {
          to: "/licenses/cms",
          label: "Result 5",
        },
      ]
    }
  },

  methods: {
    onKey(event) {
      if (event.key === 'ArrowDown') {
        if (this.active < this.results.length - 1) {
          this.active++
        } else {
          this.active = 0
        }
        return null
      }

      if (event.key === 'ArrowUp') {
        if (this.active > 0) {
          this.active--
        } else {
          this.active = this.results.length - 1
        }
        return null
      }

      if (event.key === 'Enter') {
        const $list = this.$refs.searchResults.children
        const $li = $list[this.active]
        const $a = $li.children[0]

        $a.click()
        this.$emit('selectResult')
      }
    }
  },

  mounted() {
    window.addEventListener('keydown', this.onKey);
  },

  unmounted() {
    window.removeEventListener('keydown', this.onKey);
  }
}
</script>