<template>
  <div class="mt-16 container mx-auto max-w-md">
    <div class="px-8">
      <img
        class="max-w-none h-10"
        src="~@/common/images/craftcms.svg" />
    </div>

    <form
      method="POST"
      class="p-8 bg-white dark:bg-gray-800 rounded-md shadow-xl mt-8 space-y-4">
      <input
        type="hidden"
        :name="csrfTokenName"
        :value="csrfTokenValue" />
      <input
        type="hidden"
        name="redirect"
        :value="redirect" />

      <div class="space-y-4">
        <p class="m-0">
          <strong>The following authorizations are required:</strong>
        </p>

        <ul class="list-disc ml-6">
          <template
            v-for="(scope, scopeKey) in oauthScopes"
            :key="scopeKey">
            <li>{{ scope }}</li>
          </template>
        </ul>

        <p class="m-0 text-sm text-light">By clicking “Approve” you
          allow this application and id.craftcms.com to use your data
          according to our terms of services.</p>
      </div>

      <div>
        <checkbox
          name="rememberMe"
          value="1"
          label="Remember me for 30 days" />
      </div>

      <div class="flex justify-between items-center">
        <div class="text-sm text-light flex items-center">
          <template v-if="!isSecureConnection">
            <icon
              class="mr-1 w-4 h-4"
              icon="lock-closed" />
            Your connection is secure
          </template>
          <template v-else>
            <icon
              class="mr-1 w-4 h-4"
              icon="lock-open" />
            Your connection is insecure
          </template>
        </div>

        <div class="space-x-2">
          <btn
            type="submit"
            name="deny"
            value="Deny">Cancel
          </btn>
          <btn
            kind="primary"
            type="submit"
            name="approve"
            value="Approve">Approve
          </btn>
        </div>
      </div>
    </form>
  </div>
</template>


<script>
export default {
  computed: {
    csrfTokenName() {
      return window.Craft.csrfTokenName
    },
    csrfTokenValue() {
      return window.Craft.csrfTokenValue
    },
    isSecureConnection() {
      return window.isSecureConnection
    },
    oauthScopes() {
      return window.oauthScopes
    },
    redirect() {
      return window.redirect
    }
  }
}
</script>