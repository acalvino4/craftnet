<template>
  <form
    v-if="userDraft"
    @submit.prevent="save()">
    <h1>Profile</h1>

    <textbox
      id="developerName"
      label="Developer Name"
      v-model="userDraft.developerName"
      :errors="errors.developerName" />
    <textbox
      id="developerUrl"
      label="Developer URL"
      v-model="userDraft.developerUrl"
      :errors="errors.developerUrl" />
    <textbox
      id="location"
      label="Location"
      v-model="userDraft.location"
      :errors="errors.location" />

    <hr />

    <h2>Photo</h2>

    <div class="flex">
      <div class="">
        <img
          ref="photo"
          :src="userDraft.photoUrl"
          style="width: 150px; height: 150px;"
          class="img-thumbnail mr-3" />
      </div>
      <div>
        <template v-if="userDraft.photoId">
          <field>
            <btn
              :disabled="loading.uploadPhoto"
              :loading="loading.uploadPhoto"
              @click="changePhoto">Change Photo
            </btn>
          </field>
          <field>
            <btn
              kind="danger"
              icon="times"
              :disabled="loading.deletePhoto"
              :loading="loading.deletePhoto"
              @click="deletePhoto">Delete
            </btn>
          </field>
        </template>
        <template v-else>
          <field>
            <btn
              :disabled="loading.uploadPhoto"
              :loading="loading.uploadPhoto"
              @click="changePhoto">Upload a photo
            </btn>
          </field>
        </template>

        <input
          type="file"
          ref="photoFile"
          class="hidden"
          @change="onChangePhoto" />
      </div>
    </div>

    <hr />

    <p class="text-secondary"><em>Your profile data is being used for your
      developer page on the Plugin Store.</em></p>

    <btn
      kind="primary"
      type="submit"
      :disabled="loading.page"
      :loading="loading.page">Save
    </btn>
  </form>
</template>

<script>
import {mapState, mapGetters} from 'vuex'
import ConnectedApps from '../../components/developer/connected-apps/ConnectedApps'

export default {
  components: {
    ConnectedApps,
  },

  data() {
    return {
      loading: {
        page: false,
        uploadPhoto: false,
        deletePhoto: false,
      },
      userDraft: {},
      password: '',
      newPassword: '',
      errors: {},
    }
  },

  computed: {
    ...mapState({
      user: state => state.account.user,
    }),

    ...mapGetters({
      userIsInGroup: 'account/userIsInGroup',
    }),
  },

  methods: {
    /**
     * Delete photo.
     */
    deletePhoto() {
      if (confirm("Are you sure you want to delete this image?")) {
        this.loading.deletePhoto = true

        this.$store.dispatch('account/deleteUserPhoto')
          .then(response => {
            if (response.data && !response.data.error) {
              this.$store.dispatch('app/displayNotice', 'Photo deleted.')
              this.userDraft.photoId = response.data.photoId
              this.userDraft.photoUrl = response.data.photoUrl
            } else {
              this.$store.dispatch('app/displayError', response.data.error)
            }

            this.loading.deletePhoto = false
          })
          .catch(response => {
            this.loading.deletePhoto = false

            const errorMessage = response.data && response.data.error ? response.data.error : 'Couldn’t delete photo.'
            this.$store.dispatch('app/displayError', errorMessage)

            this.errors = response.data && response.data.errors ? response.data.errors : {}
          })
      }
    },

    /**
     * Change photo.
     */
    changePhoto() {
      this.$refs.photoFile.click()
    },

    /**
     * On change photo.
     */
    onChangePhoto() {
      this.loading.uploadPhoto = true

      let data = {
        photo: this.$refs.photoFile.files[0],
        photoUrl: this.userDraft.photoUrl,
      }

      this.$store.dispatch('account/uploadUserPhoto', data)
        .then(response => {
          if (response.data && !response.data.error) {
            this.$store.dispatch('app/displayNotice', 'Photo uploaded.')
            let photoUrl = response.data.photoUrl
            this.userDraft.photoId = response.data.photoId
            this.userDraft.photoUrl = photoUrl + (photoUrl.match(/\?/g) ? '&' : '?') + Math.floor(Math.random() * 1000000)
            this.errors = {}

            this.loading.uploadPhoto = false
          } else {
            this.$store.dispatch('app/displayError', response.data.error)
            this.loading.uploadPhoto = false
          }
        })
        .catch(response => {
          this.loading.uploadPhoto = false

          const errorMessage = response.data && response.data.error ? response.data.error : 'Couldn’t upload photo.'
          this.$store.dispatch('app/displayError', errorMessage)

          this.errors = response.data && response.data.errors ? response.data.errors : {}
        })
    },

    /**
     * Save the profile.
     */
    save() {
      this.loading.page = true

      this.$store.dispatch('account/saveUser', {
          id: this.userDraft.id,
          developerName: this.userDraft.developerName,
          developerUrl: this.userDraft.developerUrl,
          location: this.userDraft.location,
          photoUrl: this.userDraft.photoUrl,
        })
        .then(() => {
          this.$store.dispatch('app/displayNotice', 'Settings saved.')
          this.errors = {}
          this.loading.page = false
        })
        .catch((response) => {
          this.loading.page = false
          const errorMessage = response.data && response.data.error ? response.data.error : 'Couldn’t save profile.'
          this.$store.dispatch('app/displayError', errorMessage)
          this.errors = response.data && response.data.errors ? response.data.errors : {}
        })
    }
  },

  mounted() {
    this.userDraft = JSON.parse(JSON.stringify(this.user))
  }
}
</script>
