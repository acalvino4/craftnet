<template>
  <div
    :class="{
      'w-7 h-7 rounded overflow-hidden flex items-center justify-center': true,
      'bg-gray-300 dark:bg-gray-400': !photoUrl,
    }"
  >
    <template v-if="user">
      <template v-if="user.photoUrl">
        <img :src="user.photoUrl" />
      </template>
      <template v-else>
        <icon
          icon="user"
          class="w-3 h-3 text-gray-500" />
      </template>
    </template>

    <template v-else-if="organization && organization.avatar">
        <img
          :src="staticImageUrl('avatars/' + organization.avatar)" />
    </template>
  </div>
</template>

<script>
export default {
  props: {
    user: {
      type: Object,
      required: false,
    },
    organization: {
      type: Object,
      required: false,
    },
  },
  computed: {
    photoUrl() {
      if(this.organization && this.organization.avatar) {
        return this.staticImageUrl('avatars/' + this.organization.avatar);
      }

      if (this.user && this.user.photoUrl) {
        return this.user.photoUrl;
      }

      return null
    }
  }
}
</script>