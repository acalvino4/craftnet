<template>
  <div>
    <page-header>
      <div>
        <p>
          <router-link
            class="nav-link"
            to="/developer/plugins"
            exact>
            ←
            Plugins
          </router-link>
        </p>

        <h1 v-if="plugin">{{ plugin.name }}</h1>
        <h1 v-else>Add a plugin</h1>
      </div>
    </page-header>

    <template v-if="!pluginId && !this.pluginDraft.repository">
      <pane>
        <p>To get started, select a repository for your plugin.</p>

        <template v-if="appsLoading">
          <spinner></spinner>
        </template>
        <template v-else>
          <template v-if="connectedAppsCount > 0">
            <div
              v-for="(app, appHandle) in apps"
              class="mb-3"
              :key="appHandle">
              <repositories
                :appHandle="appHandle"
                :loading-repository="loadingRepository"
                @selectRepository="onSelectRepository"></repositories>
            </div>
          </template>
          <template v-else>
            <template v-if="connectedAppsCount > 0">
              <div
                v-for="(app, appHandle) in apps"
                class="mb-3"
                :key="appHandle">
                <repositories
                  :appHandle="appHandle"
                  :loading-repository="loadingRepository"
                  @selectRepository="onSelectRepository"></repositories>
              </div>
            </template>
            <template v-else>
              <h2>Connect</h2>
              <p>Connect to GitHub to retrieve your
                repositories.</p>
              <connected-apps></connected-apps>
            </template>
          </template>
        </template>

        <div class="mt-6">
          <btn to="/settings/developer">Manage connected apps
          </btn>
        </div>
      </pane>
    </template>

    <template v-else>
      <div
        v-if="plugin && !plugin.enabled"
        role="alert"
        class="alert alert-info">

        <template v-if="plugin.pendingApproval">
          Your plugin is being reviewed, it will be automatically
          published once it’s approved.
        </template>
        <template v-else>
          <template
            v-if="plugin.lastHistoryNote && plugin.lastHistoryNote.devComments">
            <h6>Changes requested</h6>
            <div v-html="plugin.lastHistoryNote.devComments"></div>
            <btn
              small
              @click="submit()">Re-submit for Approval
            </btn>
          </template>
          <template v-else>
            <btn
              small
              @click="submit()">Submit for Approval
            </btn>
          </template>

          <span class="text-light">Your plugin will be automatically published once it’s approved.</span>
        </template>
        <spinner v-if="pluginSubmitLoading"></spinner>
      </div>

      <form @submit.prevent="save()">
        <pane class="mb-6">
          <template v-slot:header>GitHub Repository</template>
          <template v-slot:default>
            <field
              :first="true"
              :horizontal="true"
              label="Repository URL"
              label-for="repository"
              :errors="errors.repository"
            >
              <textbox
                id="repository"
                v-model="pluginDraft.repository"
                :disabled="true" />
            </field>
          </template>
        </pane>

        <pane class="mb-6">
          <template v-slot:header>Plugin Icon</template>
          <template v-slot:default>
            <div class="flex">
              <div class="mr-6">
                <field :first="true">
                  <img
                    :src="pluginDraft.iconUrl"
                    class="w-16 h-16" />
                </field>
              </div>
              <div class="flex-1">
                <field :first="true">
                  <div class="instructions">
                    <p>The plugin icon must be a square SVG
                      file that should not exceed
                      {{ maxUploadSize }}, and should not
                      contain embedded images or
                      fonts.</p>
                  </div>
                  <input
                    type="file"
                    ref="iconFile"
                    class="form-control"
                    @change="changeIcon"
                    :class="{'is-invalid': errors.iconId }" />
                  <div
                    class="invalid-feedback"
                    v-for="(error, errorKey) in iconErrors"
                    :key="'plugin-icon-error-' + errorKey">
                    {{ error }}
                  </div>
                </field>
              </div>
            </div>
          </template>
        </pane>

        <pane class="mb-6">
          <template v-slot:header>Plugin Details</template>
          <template v-slot:default>
            <field
              :first="true"
              label-for="name"
              label="Name"
              :errors="errors.name">
              <textbox
                id="name"
                v-model="pluginDraft.name"
                @input="onInputName" />
            </field>

            <field
              label-for="packageName"
              label="Package Name"
              :errors="errors.packageName">
              <textbox
                id="packageName"
                v-model="pluginDraft.packageName"
                :disabled="true" />
            </field>

            <field
              label-for="handle"
              label="Plugin Handle"
              :errors="errors.handle">
              <textbox
                id="handle"
                v-model="pluginDraft.handle"
                :disabled="true" />
            </field>

            <field
              label-for="licenseType"
              label="License">
              <dropdown
                id="licenseType"
                :fullwidth="true"
                v-model="pluginDraft.license"
                :options="[
                                {label: 'Craft', value: 'craft'},
                                {label: 'MIT', value: 'mit'},
                            ]" />
            </field>

            <field
              label-for="shortDescription"
              label="Short Description"
              :errors="errors.shortDescription">
              <textbox
                id="shortDescription"
                v-model="pluginDraft.shortDescription" />
            </field>

            <field
              label-for="longDescription"
              label="Long Description"
              :errors="errors.longDescription">
              <textbox
                type="textarea"
                id="longDescription"
                v-model="pluginDraft.longDescription"
                rows="16" />
              <p class="text-secondary"><small>Styling with
                Markdown
                is supported.</small></p>
            </field>

            <field
              label-for="documentationUrl"
              label="Documentation URL"
              :errors="errors.documentationUrl">
              <textbox
                id="documentationUrl"
                v-model="pluginDraft.documentationUrl" />
            </field>

            <field
              label-for="changelogPath"
              label="Changelog Path"
              instructions="The path to your changelog file, relative to the repository root."
              :errors="errors.changelogPath">
              <textbox
                id="changelogPath"
                v-model="pluginDraft.changelogPath" />
            </field>

            <plugin-categories
              v-model:plugin-draft="pluginDraft"></plugin-categories>

            <field
              label-for="keywords"
              label="Keywords"
              instructions="Comma-separated list of keywords."
              :errors="errors.keywords">
              <textbox
                id="keywords"
                v-model="pluginDraft.keywords" />
            </field>

            <field
              label-for="abandoned"
              label="Abandoned plugin?">
              <lightswitch
                id="abandoned"
                v-model:checked="pluginDraft.abandoned"
              />
            </field>


            <template v-if="pluginDraft.abandoned">
              <field
                label-for="replacement"
                label="Replacement Plugin"
                instructions="The handle of the replacement plugin. Leave this field empty if you don’t want to specify a replacement plugin."
                :errors="errors.replacementHandle">
                <textbox
                  v-model="pluginDraft.replacementHandle" />
              </field>
            </template>
          </template>
        </pane>

        <pane class="mb-6">
          <template v-slot:header>Screenshots</template>
          <template v-slot:default>
            <field
              :first="true"
              :instructions="'Plugin screenshots must be JPG or PNG files, and should not exceed '+maxUploadSize+'.'">
              <input
                type="file"
                ref="screenshotFiles"
                class="form-control"
                multiple="">
            </field>

            <div
              ref="screenshots"
              class="d-inline">
              <draggable
                v-model="screenshots"
                item-key="id">
                <template #item="{element, index}">
                  <div class="screenshot">
                    <img
                      :src="element.url"
                      class="img-thumbnail mr-3 mb-3" />

                    <button
                      class="text-black dark:text-white-inverse bg-red-500 hover:bg-red-600 p-1.5 absolute -top-1 -right-1 rounded-full transform -translate-y-2 translate-x-2"
                      @click="removeScreenshot(index);">
                      <icon
                        icon="minus"
                        class="w-4 h-4" />
                    </button>
                  </div>
                </template>
              </draggable>
            </div>
          </template>
        </pane>

        <pane class="mb-6">
          <template v-slot:header>Editions</template>
          <template v-slot:default>
            <div
              v-for="(edition, editionKey) in pluginDraft.editions"
              :key="'edition-' + editionKey">
              <div class="flex">
                <div class="w-1/4">
                  <h2>{{ edition.name }}</h2>
                  <p class="text-grey">
                    <code>{{ edition.handle }}</code></p>
                </div>
                <div class="w-3/4">
                  <field
                    :first="true"
                    :label-for="edition.handle+'-price'"
                    label="License Price"
                    :errors="errors['editions['+editionKey+'].price']">
                    <textbox
                      :id="edition.handle+'-price'"
                      v-model="edition.price"
                      :disabled="plugin && plugin.enabled && !parseFloat(edition.price)" />
                  </field>

                  <field
                    :label-for="edition.handle+'-renewalPrice'"
                    label="Renewal Price"
                    :errors="errors['editions['+editionKey+'].renewalPrice']"
                  >
                    <textbox
                      :id="edition.handle+'-renewalPrice'"
                      v-model="edition.renewalPrice"
                      :disabled="plugin && plugin.enabled && !parseFloat(edition.price)" />
                  </field>

                  <field
                    v-if="pluginDraft.editions.length > 1"
                    id="features"
                    label="Features"
                  >
                    <table
                      v-if="edition.features.length > 0"
                      id="features"
                      class="table border mb-4">
                      <thead>
                      <tr>
                        <th class="w-1/3">Name</th>
                        <th>Description</th>
                        <th></th>
                      </tr>
                      </thead>
                      <tbody>
                      <tr
                        v-for="(feature, featureKey) in edition.features"
                        :key="'feature-'+featureKey">
                        <td>
                          <textbox
                            :id="edition.handle+'-featureName'"
                            v-model="feature.name" />
                        </td>
                        <td>
                          <textbox
                            :id="edition.handle+'-featureDescription'"
                            v-model="feature.description" />
                        </td>
                        <td class="w-10 text-center">
                          <a @click.prevent="removeFeature(editionKey, featureKey)">
                            <icon
                              icon="x"
                              class="w-5 h-5 text-red-500"
                            />
                          </a>
                        </td>
                      </tr>
                      </tbody>
                    </table>

                    <div>
                      <btn
                        @click="addFeature(editionKey)">
                        <icon
                          icon="plus"
                          class="w-4 h-4" />
                        Add a feature
                      </btn>
                    </div>
                  </field>
                </div>
              </div>
              <hr />
            </div>

            <p class="text-center">To manage your editions, please
              <a href="mailto:hello@craftcms.com">contact us</a>.
            </p>
          </template>
        </pane>

        <div>
          <btn
            kind="primary"
            type="submit"
            :disabled="loading"
            :loading="loading">Save
          </btn>
        </div>
      </form>
    </template>
  </div>
</template>

<script>
/* global Craft */

import {mapState, mapGetters} from 'vuex'
import pluginsApi from '../../../api/plugins'
import ConnectedApps from '../../../components/developer/connected-apps/ConnectedApps'
import Repositories from '../../../components/developer/Repositories'
import PluginCategories from '../../../components/developer/PluginCategories'
import slug from 'limax'
import draggable from 'vuedraggable'
import qs from 'qs'
import PageHeader from '@/console/js/components/PageHeader';

export default {
  components: {
    PageHeader,
    ConnectedApps,
    Repositories,
    PluginCategories,
    draggable,
  },

  data() {
    return {
      loading: false,
      pluginSubmitLoading: false,
      repositoryLoading: false,
      loadingRepository: null,
      pluginDraft: {
        id: null,
        icon: null,
        iconId: null,
        developerId: null,
        editions: [
          {
            name: 'Standard',
            handle: 'standard',
            features: []
          }
        ],
        enabled: false,
        handle: '',
        packageName: '',
        name: '',
        shortDescription: '',
        longDescription: '',
        documentationUrl: '',
        changelogPath: '',
        repository: '',
        license: 'craft',
        price: 0,
        renewalPrice: 0,
        iconUrl: null,
        categoryIds: [],
        screenshots: [],
        screenshotIds: [],
        screenshotUrls: [],
        keywords: '',
        abandoned: false,
        replacementHandle: null,
      },
      errors: {},
    }
  },

  computed: {
    ...mapState({
      apps: state => state.apps.apps,
      appsLoading: state => state.apps.appsLoading,
      plugins: state => state.plugins.plugins,
    }),

    ...mapGetters({
      userIsInGroup: 'account/userIsInGroup',
    }),

    pluginId() {
      return this.$route.params.id
    },

    plugin() {
      return this.plugins.find(p => p.id == this.pluginId)
    },

    connectedAppsCount() {
      return Object.keys(this.apps).length
    },

    screenshots: {
      get() {
        let screenshots = []

        this.pluginDraft.screenshotIds.forEach((screenshotId, index) => {
          let screenshot = {
            id: screenshotId,
            url: this.pluginDraft.screenshotUrls[index],
          }
          screenshots.push(screenshot)
        })

        return screenshots
      },

      set(screenshots) {
        let screenshotIds = []
        let screenshotUrls = []

        screenshots.forEach(screenshot => {
          screenshotIds.push(screenshot.id)
          screenshotUrls.push(screenshot.url)
        })

        this.pluginDraft.screenshotIds = screenshotIds
        this.pluginDraft.screenshotUrls = screenshotUrls
      }
    },

    maxUploadSize() {
      return this.humanFileSize(Craft.maxUploadSize)
    },

    iconErrors() {
      let errors = []

      if (this.errors.iconId) {
        errors = [...errors, ...this.errors.iconId]
      }

      if (this.errors.icon) {
        errors = [...errors, ...this.errors.icon]
      }

      return errors;
    }
  },

  methods: {
    /**
     * On input name.
     *
     * @param name
     */
    onInputName(name) {
      if (!this.pluginId) {
        const handle = slug(name)
        this.pluginDraft.handle = handle
      }
    },

    /**
     * On select repository.
     *
     * @param repository
     */
    onSelectRepository(repository) {
      this.loadDetails(repository.html_url)
    },

    /**
     * Remove screenshot.
     *
     * @param key
     */
    removeScreenshot(key) {
      this.pluginDraft.screenshotUrls.splice(key, 1)
      this.pluginDraft.screenshotIds.splice(key, 1)

      let removeBtns = this.$refs.screenshots.getElementsByClassName('btn')

      for (let i = 0; i < removeBtns.length; i++) {
        removeBtns[i].blur()
      }
    },

    /**
     * Change screenshots.
     */
    changeScreenshots() {
      this.pluginDraft.screenshotUrls = []

      let files = this.$refs.screenshotFiles.files

      for (let i = 0; i < files.length; i++) {
        let reader = new FileReader()

        reader.onload = function(e) {
          let screenshotUrl = e.target.result
          this.pluginDraft.screenshotUrls.push(screenshotUrl)
        }.bind(this)

        reader.readAsDataURL(files[i])
      }
    },

    /**
     * Change icon.
     *
     * @param ev
     */
    changeIcon(ev) {
      this.pluginDraft.icon = ev.target.value

      let reader = new FileReader()

      reader.onload = function(e) {
        this.pluginDraft.iconUrl = e.target.result
      }.bind(this)

      reader.readAsDataURL(this.$refs.iconFile.files[0])
    },

    /**
     * Load details.
     *
     * @param repositoryUrl
     */
    loadDetails(repositoryUrl) {
      this.repositoryLoading = true
      this.loadingRepository = repositoryUrl

      let body = {
        repository: encodeURIComponent(url)
      }

      let params = qs.stringify(body)
      let url = repositoryUrl

      pluginsApi.loadDetails(url, params)
        .then((response) => {
          this.repositoryLoading = false
          this.loadingRepository = null

          if (response.data.error) {
            this.$store.dispatch('app/displayError', response.data.error)
          } else {
            this.pluginDraft.repository = repositoryUrl

            if (response.data.changelogPath) {
              this.pluginDraft.changelogPath = response.data.changelogPath
            }

            if (response.data.documentationUrl) {
              this.pluginDraft.documentationUrl = response.data.documentationUrl
            }

            if (response.data.name) {
              this.pluginDraft.name = response.data.name
            }

            if (response.data.handle) {
              this.pluginDraft.handle = response.data.handle
            }

            if (response.data.shortDescription) {
              this.pluginDraft.shortDescription = response.data.shortDescription
            }

            if (response.data.packageName) {
              this.pluginDraft.packageName = response.data.packageName
            }

            if (response.data.iconId) {
              this.pluginDraft.iconId = response.data.iconId
            }

            if (response.data.iconUrl) {
              this.pluginDraft.iconUrl = response.data.iconUrl
            }

            if (response.data.license) {
              this.pluginDraft.license = response.data.license
            }

            if (response.data.keywords) {
              this.pluginDraft.keywords = response.data.keywords.join(', ')
            }
          }
        })
        .catch((error) => {
          this.repositoryLoading = false
          const errorMessage = error.response.data && error.response.data.error ? error.response.data.error : 'Couldn’t load repository.'
          this.$store.dispatch('app/displayError', errorMessage)
        })
    },

    /**
     * Save the plugin.
     */
    save() {
      this.loading = true

      let plugin = {
        icon: this.$refs.iconFile.files[0],
        handle: this.pluginDraft.handle,
        packageName: this.pluginDraft.packageName,
        name: this.pluginDraft.name,
        shortDescription: this.pluginDraft.shortDescription,
        longDescription: this.pluginDraft.longDescription,
        documentationUrl: this.pluginDraft.documentationUrl,
        changelogPath: this.pluginDraft.changelogPath,
        repository: this.pluginDraft.repository,
        license: this.pluginDraft.license,
        keywords: this.pluginDraft.keywords,
        categoryIds: [],
        screenshotIds: [],
        editions: this.pluginDraft.editions,
        abandoned: this.pluginDraft.abandoned,
        replacementHandle: this.pluginDraft.replacementHandle,
      }

      if (this.pluginDraft.iconId) {
        plugin.iconId = [parseInt(this.pluginDraft.iconId)]
      }

      if (this.pluginDraft.id) {
        plugin.pluginId = this.pluginDraft.id
      }

      if (this.pluginDraft.categoryIds.length > 0) {
        plugin.categoryIds = this.pluginDraft.categoryIds
      }

      if (this.$refs.screenshotFiles.files.length > 0) {
        plugin.screenshots = this.$refs.screenshotFiles.files
      }

      if (this.pluginDraft.screenshotUrls.length > 0) {
        plugin.screenshotUrls = this.pluginDraft.screenshotUrls
      }

      if (this.pluginDraft.screenshotIds.length > 0) {
        plugin.screenshotIds = this.pluginDraft.screenshotIds
      }

      this.$store.dispatch('plugins/savePlugin', {plugin})
        .then(() => {
          this.loading = false
          this.$store.dispatch('app/displayNotice', 'Plugin saved.')
          this.$router.push({path: '/developer/plugins'})
        })
        .catch((response) => {
          this.loading = false
          this.errors = response.data && response.data.errors ? response.data.errors : {}

          const errorMessage = response.data && response.data.error ? response.data.error : 'Couldn’t save plugin.'
          this.$store.dispatch('app/displayError', errorMessage)
        })
    },

    /**
     * Submit plugin for approval.
     */
    submit() {
      this.pluginSubmitLoading = true
      this.$store.dispatch('plugins/submitPlugin', this.plugin.id)
        .then(() => {
          this.pluginSubmitLoading = false
          this.$store.dispatch('app/displayNotice', 'Plugin submitted for approval.')
        })
        .catch((response) => {
          this.pluginSubmitLoading = false
          this.errors = response.data && response.data.errors ? response.data.errors : {}

          const errorMessage = response.data && response.data.error ? response.data.error : 'Couldn’t submit plugin for approval.'
          this.$store.dispatch('app/displayError', errorMessage)
        })
    },

    /**
     * Human file size.
     *
     * @param bytes
     * @returns {string}
     */
    humanFileSize(bytes) {
      const threshold = 1024

      if (bytes < threshold) {
        return bytes + ' B'
      }

      const units = ['kB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB']

      let u = -1

      do {
        bytes = bytes / threshold
        ++u
      }
      while (bytes >= threshold)

      return bytes.toFixed(1) + ' ' + units[u]
    },

    addFeature(editionKey) {
      this.pluginDraft.editions[editionKey].features.push({})
    },

    removeFeature(editionKey, featureKey) {
      this.pluginDraft.editions[editionKey].features.splice(featureKey, 1)
    }
  },

  mounted() {
    this.$store.dispatch('apps/getApps')
      .catch((response) => {
        const errorMessage = response.data && response.data.error ? response.data.error : 'Couldn’t get apps.'
        this.$store.dispatch('app/displayError', errorMessage)
      })

    if (this.plugin) {
      this.pluginDraft = JSON.parse(JSON.stringify(this.plugin))

      if (!this.pluginDraft.price) {
        this.pluginDraft.price = 0
      }

      if (!this.pluginDraft.renewalPrice) {
        this.pluginDraft.renewalPrice = 0
      }
    } else {
      if (this.pluginId) {
        this.$router.push({path: '/developer/plugins'})
      }
    }
  },
}
</script>

<style lang="scss">
.screenshot {
  position: relative;
  display: inline-block;
  width: 230px;
  margin-right: 24px;
  margin-top: 14px;
}

.screenshot {
  .c-btn {
    position: absolute;
    top: -10px;
    right: -10px;
  }
}
</style>
