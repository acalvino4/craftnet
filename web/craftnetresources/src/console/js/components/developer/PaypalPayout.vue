<template>
  <form
    v-if="userDraft"
    @submit.prevent="save()"
  >
    <div class="card-body">
      <field
        :first="true"
        :vertical="true"
        label="PayPal Email Address"
      >
        <textbox
          id="paypalEmail"
          v-model="userDraft.payPalEmail"
          :errors="errors.payPalEmail"
        />
      </field>
      <btn
        class="mt-4"
        kind="primary"
        type="submit"
        :disabled="loading"
        :loading="loading">Save
      </btn>
    </div>
  </form>
</template>

<script>
import {mapState} from 'vuex'

export default {
  data() {
    return {
      loading: false,
      photoLoading: false,
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
  },

  methods: {
    /**
     * Save the settings.
     */
    save() {
      this.errors = {}
      this.loading = true

      this.$store.dispatch('account/saveUser', {
          id: this.userDraft.id,
          payPalEmail: this.userDraft.payPalEmail,
        })
        .then(() => {
          this.loading = false
          this.$store.dispatch('app/displayNotice', 'Payout settings saved.')

        })
        .catch(response => {
          this.loading = false
          this.errors = response.data.errors
          this.$store.dispatch('app/displayError', 'Couldn’t save payout settings.')
        })
    }
  },

  mounted() {
    this.userDraft = JSON.parse(JSON.stringify(this.user))
  }
}
</script>
