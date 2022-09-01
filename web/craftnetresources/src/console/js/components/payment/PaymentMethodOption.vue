<template>
    <div
      class="rounded-lg rounded p-4 flex items-center cursor-pointer border dark:border-gray-700"
      :class='[
            checked ? "outline outline-1 outline-blue-600 border-blue-600" : "border-gray-300 hover:border-gray-400",
            active ? "group-focus-visible:ring group-focus-visible:ring-3 group-focus-visible:ring-offset-1 group-focus-visible:ring-blue-600/30" : ""
          ]'
    >
      <template v-if="paymentMethod">
        <div class="flex-1 flex">
          <div>
            <profile-photo
              class="mr-4"
              size="md"
              :photo-url="photoUrl"
              :fallback="paymentMethod.org ? 'org' : 'user'"
            />
          </div>
          <div class="flex-1">
            <div>
              <div class="font-bold">
                <template v-if="paymentMethod.org">
                  {{ paymentMethod.org.title }}
                </template>
                <template v-else>
                  Personal
                </template>
              </div>

              <div class="text-sm text-gray-600 dark:text-gray-400 leading-snug">
                <template v-if="paymentMethod.org">
                  Licenses will be assigned to the {{paymentMethod.org.title}} organization.
                </template>
                <template v-else>
                  Licenses will be assigned to your user.
                </template>
              </div>

              <div class="mt-4 font-mono">
                <div>
                  <span class="uppercase">{{ paymentMethod.card.brand }}</span> **** **** **** {{ paymentMethod.card.last4 }}
                </div>

                <div class="text-sm text-gray-500">
                  {{ paymentMethod.card.exp_month }}/{{ paymentMethod.card.exp_year }}
                </div>
              </div>
            </div>
          </div>
        </div>
      </template>
      <template v-else>
        <div class="flex-1">
          <div>
            <strong>{{ name }}</strong>
          </div>
          <div class="text-sm text-gray-600">
            {{ description }}
          </div>
        </div>
        <template v-if="info">
          <div class="text-gray-500 text-right">
            <small>
              Licenses will be assigned to
            </small>
            <div>
              {{ info }}
            </div>
          </div>
        </template>
      </template>
    </div>
</template>

<script>

import ProfilePhoto from '../ProfilePhoto';
import {mapState} from 'vuex';
export default {
  components: {ProfilePhoto},
  props: {
    name: {
      type: String,
      required: true,
    },
    description: {
      type: String,
      required: true,
    },
    info: {
      type: String,
      required: false,
    },
    paymentMethod: {
      type: Object,
      required: false,
    },
    active: {
      type: Boolean,
      required: false,
    },
    checked: {
      type: Boolean,
      required: false,
    },
  },

  computed: {
    ...mapState({
      user: state => state.account.user,
    }),

    photoUrl() {
      if (this.paymentMethod.org) {
        if (!this.paymentMethod.org.orgLogo) {
          return null
        }

        return this.paymentMethod.org.orgLogo.url
      }

      if (!this.user.photoUrl) {
        return null
      }

      return this.user.photoUrl
    }
  }
}
</script>