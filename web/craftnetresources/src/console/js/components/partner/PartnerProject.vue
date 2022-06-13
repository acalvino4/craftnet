<template>
  <div v-if="draftProject">
    <div v-show="!isEditing">
      <div class="flex">
        <div class="mr-6">
          <icon
            icon="grip-vertical"
            class="w-4 h-4 text-light mt-1.5" />
        </div>
        <ul class="flex-1">
          <li
            v-if="draftProject.name"
            class="mb-3"><strong
            class="text-xl">{{ draftProject.name }}</strong></li>
          <li v-if="draftProject.role">Role: {{
              draftProject.role
            }}
          </li>
          <li v-if="draftProject.url">{{ linkTypeDisplay }} Link: <a
            :href="draftProject.url"
            target="_blank">{{ draftProject.url }}</a></li>
          <li
            v-if="draftProject.withCraftCommerce"
            class="mt-3">
            &#10004; This project includes Craft Commerce
          </li>
          <li
            v-if="draftProject.screenshots.length"
            class="mt-3">
            <strong>Screenshots</strong></li>
          <li class="flex">
            <div
              v-for="(screenshot, index) in draftProject.screenshots"
              :key="index"
              class="p-1 mt-2 inline-block bg-gray-100 flex align-middle justify-center"
              style="height: 140px; width: 240px;">
              <img
                :src="screenshot.url"
                style="max-width: 100%; max-height: 100%;">
            </div>
          </li>
        </ul>
        <div>
          <btn @click="$emit('edit', index)">
            <icon
              icon="pencil"
              :size="null"
              class="w-3 h-3" />
            Edit
          </btn>
        </div>
      </div>
    </div>

    <modal
      v-if="isEditing"
      :show="isEditing"
      transition="fade"
      modal-type="wide">
      <template v-slot:body>
        <form
          class="p-4"
          @submit.prevent="save()">
          <field
            :vertical="true"
            :first="true"
            label-for="name"
            label="Project Name*"
            :errors="localErrors.name">
            <textbox
              id="name"
              v-model="draftProject.name"
              :is-invalid="!!localErrors.name" />
          </field>
          <field
            :vertical="true"
            label-for="role"
            label="Role"
            instructions="e.g. “Craft Commerce with custom Hubspot integration” or “Design and custom plugin development”. Max 55 characters."
            :errors="localErrors.role">
            <textbox
              id="role"
              v-model="draftProject.role"
              :max="55"
              :is-invalid="!!localErrors.role" />
          </field>
          <field
            :vertical="true"
            label-for="url"
            label="URL*"
            :errors="localErrors.url">
            <textbox
              id="url"
              v-model="draftProject.url"
              :is-invalid="!!localErrors.url" />
          </field>

          <field
            :vertical="true"
            label-for="linkType"
            label="Link Type"
            :errors="localErrors.linkType">
            <dropdown
              id="linkType"
              v-model="draftProject.linkType"
              :options="options.linkType"
              :is-invalid="!!localErrors.linkType" />
          </field>

          <field :vertical="true">
            <checkbox
              id="withCraftCommerce"
              label="This project includes Craft Commerce"
              v-model="draftProject.withCraftCommerce"
              :checked-value="1" />
          </field>

          <field
            :vertical="true"
            label="Screenshots*"
            instructions="1 to 5 JPG screenshots required with a 12:7 aspect ratio. 1200px wide will do. Drag to re-order."
            :errors="localErrors.screenshots">
            <draggable
              v-model="draftProject.screenshots"
              item-key="project">
              <template #item="{element, index}">
                <div>
                  <img
                    :src="element.url"
                    class="img-thumbnail mr-3 mb-2"
                    style="max-width: 200px; max-height: 200px;" />
                  <btn
                    kind="danger"
                    :small="true"
                    @click="removeScreenshot(index)"
                    class="">
                    <icon
                      icon="x"
                      class="w-4 h-4" />
                    Remove
                  </btn>
                </div>
              </template>
            </draggable>
          </field>


          <!-- JPEG with 12x7 1200 x 700 -->

          <div
            v-if="draftProject.screenshots.length <= 5"
            class="mt-4">
            <input
              type="file"
              accept=".jp2,.jpeg,.jpg,.jpx"
              @change="screenshotFileChange"
              ref="screenshotFiles"
              class="hidden"
              multiple="">
            <btn
              small
              :disabled="isUploading"
              @click="$refs.screenshotFiles.click()">
              <template v-if="!isUploading">
                <icon
                  icon="plus"
                  class="w-4 h-4" />
              </template>
              <span v-show="!isUploading"> Add screenshots</span>
              <span v-show="isUploading && uploadProgress < 100">Uploading: {{
                  uploadProgress
                }}%</span>
              <span v-show="isUploading && uploadProgress == 100">Processing, please wait</span>
            </btn>
            <spinner v-show="isUploading"></spinner>
          </div>

          <hr />

          <div class="mt-4 flex justify-between">
            <div class="flex items-center">
              <btn
                class="mr-3"
                :disabled="requestPending"
                @click="$emit('cancel', index)">Cancel
              </btn>

              <btn
                class="mr-3"
                type="submit"
                kind="primary"
                :disabled="requestPending">Save
              </btn>

              <spinner
                :class="{'invisible': !requestPending}"></spinner>
            </div>
            <div>
              <btn
                v-if="draftProject.id !== 'new'"
                kind="danger"
                :disabled="requestPending"
                @click="$emit('delete', index)">Delete
              </btn>
            </div>
          </div>
        </form>
      </template>
    </modal>
  </div>
</template>

<script>
/* global Craft */

import partnerApi from '../../api/partners'
import draggable from 'vuedraggable'
import Modal from '../Modal'

export default {
  props: ['index', 'project', 'editIndex', 'requestPending', 'errors'],

  components: {
    draggable,
    Modal,
  },

  data() {
    return {
      uploadProgress: 0,
      isUploading: false,
      options: {
        linkType: [
          {label: 'Website', value: 'website'},
          {label: 'Case Study', value: 'caseStudy'}
        ]
      },
      draftProject: null,
    }
  },

  computed: {
    isEditing() {
      return this.editIndex === this.index
    },
    localErrors() {
      // this.errors could be 'undefined'
      return this.errors || {}
    },
    linkTypeDisplay() {
      for (let i in this.options.linkType) {
        if (this.options.linkType[i].value === this.draftProject.linkType) {
          return this.options.linkType[i].label
        }
      }

      return ''
    }
  },

  methods: {
    removeScreenshot(index) {
      this.draftProject.screenshots.splice(index, 1)
    },

    screenshotFileChange(event) {
      let formData = new FormData()

      for (var i = 0; i < event.target.files.length; i++) {
        formData.append('screenshots[]', event.target.files[i])
      }

      this.isUploading = true

      partnerApi.uploadScreenshots(formData, {
          headers: {
            'X-CSRF-Token': Craft.csrfTokenValue,
          },
          onUploadProgress: (event) => {
            this.uploadProgress = Math.round(event.loaded / event.total * 100)
          }
        })
        .then(response => {
          this.isUploading = false
          this.$store.dispatch('app/displayNotice', 'Uploaded')

          let screenshots = response.data.screenshots || []

          for (let i in screenshots) {
            this.draftProject.screenshots.push(screenshots[i])
          }
        })
        .catch(error => {
          this.isUploading = false
          this.$store.dispatch('app/displayNotice', error)
        })
    },

    save() {
      this.$emit('save', {project: this.draftProject})
    }
  },

  mounted() {
    this.draftProject = JSON.parse(JSON.stringify(this.project))

    // go straight to the modal form after clicking
    // "Add New Project" button
    if (this.draftProject.id === 'new') {
      this.$emit('edit', this.index)
    }

    if (!this.draftProject.linkType) {
      this.draftProject.linkType = 'website'
    }
  },
}
</script>
