<template>
  <div>
    <spinner v-if="loading"></spinner>

    <template v-else>
      <div class="flex items-start">
        <img
          class="flex mr-3"
          :src="icon"
          height="48"
          width="48" />
        <div class="flex-1 sm:flex sm:justify-between">
          <div>
            <template v-if="connected">
              <h5>{{ accountName }}</h5>
              <p
                v-if="connectedDescription"
                class="mb-0">
                {{ connectedDescription }}</p>
              <p class="mb-0">
                <span class="text-light">{{ name }}</span>
              </p>
            </template>

            <template v-else>
              <h5>{{ name }}</h5>
              <p class="mb-0">{{ description }}</p>
            </template>

          </div>
          <div class="mt-4 sm:mt-0 sm:ml-4">
            <template v-if="connected">
              <btn
                kind="danger"
                small
                @click="$emit('disconnect')">Disconnect
              </btn>
            </template>
            <template v-else>
              <btn
                kind="primary"
                @click="$emit('connect')">
                Connect
              </btn>
            </template>

            <div
              v-if="buttonLoading"
              class="mt-2 text-right">
              <spinner></spinner>
            </div>
          </div>
        </div>
      </div>
    </template>
  </div>
</template>

<script>

export default {
  props: ['name', 'description', 'icon', 'accountName', 'connected', 'connectedDescription', 'buttonLoading', 'loading'],
}
</script>
