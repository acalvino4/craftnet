<template>
  <page-alert v-if="expiringLicensesTotal > 0">
    <template v-if="expiringLicensesTotal === 1">
      {{ expiringLicensesTotal }} {{ type }} license will lose its access to
      updates soon. Renew it now to keep getting updates.
    </template>
    <template v-else>
      {{ expiringLicensesTotal }} {{ type }} licenses will lose their access to
      updates soon. Renew them now to keep getting updates.
    </template>
  </page-alert>
</template>

<script>
import PageAlert from './PageAlert'

export default {
  props: ['expiringLicensesTotal', 'type'],

  components: {
    PageAlert,
  },
}
</script>
