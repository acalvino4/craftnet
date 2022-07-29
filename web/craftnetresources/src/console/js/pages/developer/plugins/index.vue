<template>
  <div>
    <page-header>
      <h1>Plugins</h1>

      <div>
        <btn
          kind="primary"
          to="/developer/add-plugin">
          <icon
            icon="plus"
            class="w-4 h-4" />
          New plugin
        </btn>
      </div>
    </page-header>

    <template v-if="loading">
      <spinner></spinner>
    </template>

    <template v-else>
      <div
        v-if="computedPlugins.length > 0"
        class="overflow-x-auto">
        <table class="w-full">
          <thead>
          <tr>
            <th class="py-3 px-2 text-xs font-medium uppercase text-gray-400">
              Plugin
            </th>
            <th class="py-3 px-2 text-xs font-medium uppercase text-gray-400 truncate">
              Active Installs
            </th>
            <th class="py-3 px-2 text-xs font-medium uppercase text-gray-400">
              Price
            </th>
            <th class="py-3 px-2 text-xs font-medium uppercase text-gray-400">
              Status
            </th>
          </tr>
          </thead>
          <tbody>
          <tr
            v-for="(plugin, pluginKey) in computedPlugins"
            :key="pluginKey"
            class="border-t">
            <td
              class="py-4 px-2"
              style="min-width: 300px;">
              <div class="flex items-start">
                <div class="flex-shrink-0 mt-0.5">
                  <router-link
                    :to="'/developer/plugins/' + plugin.id">
                    <img
                      v-if="plugin.iconUrl"
                      :src="plugin.iconUrl"
                      class="w-10 h-10" />
                    <div
                      v-else
                      class="bg-gray-100 rounded-full p-2">
                      <icon
                        icon="plug"
                        :size="null"
                        class="w-5 h-5 text-gray-400" />
                    </div>
                  </router-link>
                </div>

                <div class="ml-4">
                  <router-link
                    class="text-base font-medium"
                    :to="'/developer/plugins/' + plugin.id">
                    {{ plugin.name }}
                  </router-link>
                  <small
                    class="ml-2 text-light"
                    v-if="plugin.latestVersion">{{
                      plugin.latestVersion
                    }}</small>
                  <div>{{ plugin.shortDescription }}</div>
                </div>
              </div>
            </td>
            <td class="py-4 px-2">{{ plugin.activeInstalls }}</td>
            <td class="py-4 px-2">
              <div class="text-nowrap">
                {{ fullPriceLabel(plugin.id) }}
              </div>
            </td>
            <td class="py-4 px-2">
              <template v-if="plugin.enabled">
                <badge type="success">Approved</badge>
              </template>
              <template v-else>
                <badge v-if="plugin.pendingApproval">In Review
                </badge>
                <template v-else>
                  <badge
                    v-if="plugin.lastHistoryNote && plugin.lastHistoryNote.devComments"
                    type="warning">Changes requested
                  </badge>
                  <badge>Prepare for submission</badge>
                </template>
              </template>
            </td>
          </tr>
          </tbody>
        </table>
      </div>

      <empty v-else>
        <empty>
          <div class="text-center">
            <icon
              icon="plug"
              class="w-12 h-12 inline-block mb-4 text-blue-500" />

            <h2>No plugins</h2>
            <div>
              <p>You don't have any plugins.</p>
            </div>
          </div>
        </empty>
      </empty>
    </template>
  </div>
</template>

<script>
import {mapState} from 'vuex'
import Empty from '../../../components/Empty'
import PageHeader from '@/console/js/components/PageHeader'

export default {
  components: {
    Empty,
    PageHeader,
  },

  data() {
    return {
      loading: false,
    }
  },

  computed: {
    ...mapState({
      plugins: state => state.plugins.plugins,
    }),

    computedPlugins() {
      let plugins = JSON.parse(JSON.stringify(this.plugins))

      plugins.sort((a, b) => {
        if (a['name'].toLowerCase() < b['name'].toLowerCase()) {
          return -1
        }
        if (a['name'].toLowerCase() > b['name'].toLowerCase()) {
          return 1
        }
        return 0
      })

      return plugins
    },

    priceRanges() {
      let priceRanges = {}

      for (let i = 0; i < this.plugins.length; i++) {
        const plugin = this.plugins[i]
        let priceRange = this.getPriceRange(plugin.editions)
        priceRanges[plugin.id] = priceRange
      }

      return priceRanges
    },
  },

  methods: {
    getPriceRange(editions) {
      let min = null
      let max = null

      for (let i = 0; i < editions.length; i++) {
        const edition = editions[i]
        const price = parseInt(edition.price)

        if (min === null) {
          min = price
        }

        if (max === null) {
          max = price
        }

        if (price < min) {
          min = price
        }

        if (price > max) {
          max = price
        }
      }

      return {
        min,
        max
      }
    },

    fullPriceLabel(pluginId) {
      const {min, max} = this.priceRanges[pluginId]

      if (min !== max) {
        return `${this.priceLabel(min)}–${this.priceLabel(max)}`
      }

      return this.priceLabel(min)
    },

    priceLabel(price) {
      return price > 0 ? this.$filters.currency(price) : 'Free'
    }
  },

  mounted() {
    if (this.plugins.length === 0) {
      this.loading = true

      this.$store.dispatch('plugins/getPlugins')
        .then(() => {
          this.loading = false
        })
        .catch((response) => {
          this.loading = false
          const errorMessage = response.data && response.data.error ? response.data.error : 'Couldn’t get plugins.'
          this.$store.dispatch('app/displayError', errorMessage)
        })
    }
  }
}
</script>
