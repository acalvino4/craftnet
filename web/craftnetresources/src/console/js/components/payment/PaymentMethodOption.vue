<template>
    <div
      class="rounded-lg rounded p-4 flex items-center cursor-pointer border dark:border-gray-700"
      :class='[
            checked ? "outline outline-1 outline-blue-600 border-blue-600" : "border-gray-300 hover:border-gray-400",
            active ? "group-focus-visible:ring group-focus-visible:ring-3 group-focus-visible:ring-offset-1 group-focus-visible:ring-blue-600/30" : ""
          ]'
    >
      <template v-if="creditCard">
        <div class="flex-1 flex">
          <div>
            <profile-photo
              class="mr-4"
              size="md"
              :photo-url="null"
              :fallback="creditCard.org ? 'org' : 'user'"
            />
          </div>
          <div class="flex-1">
            <div>
              <div class="font-bold">
                <template v-if="creditCard.org">
                  {{ creditCard.org.title }}
                </template>
                <template v-else>
                  Personal
                </template>
              </div>

              <div class="text-sm text-gray-600 dark:text-gray-400 leading-snug">
                <template v-if="creditCard.org">
                  Licenses will be assigned to the {{creditCard.org.title}} organization.
                </template>
                <template v-else>
                  Licenses will be assigned to your user.
                </template>
              </div>

              <div class="mt-4 font-mono">
                <div>
                  <span class="uppercase">{{ creditCard.card.brand }}</span> **** **** **** {{ creditCard.card.last4 }}
                </div>

                <div class="text-sm text-gray-500">
                  {{ creditCard.card.exp_month }}/{{ creditCard.card.exp_year }}
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
    creditCard: {
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
  }
}
</script>