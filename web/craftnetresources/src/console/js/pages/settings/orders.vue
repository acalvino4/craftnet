<template>
  <div>
    <h1 class="m-0">Orders</h1>
    <div class="mt-6 space-y-6">
      <template v-if="!currentOrganization">
        <pane>
          <h2>Pending Approval</h2>
          <div class="mt-1">
            List orders pending approval by an org manager.
          </div>
        </pane>

        <pane>
          <h2>Order History</h2>

          <div class="mt-1">
            List order history (including which org it was made on behalf of, if any).
          </div>
        </pane>
      </template>
      <template v-else>
        <pane>
          <h2>Pending Approval</h2>
          <div class="mt-1">
            List orders pending manager approval.
          </div>
        </pane>

        <pane>
          <h2>Order History</h2>

          <div class="mt-1">
            List order history for orders created on behalf of the org, by its members (past and present), including who placed the order.
          </div>
        </pane>
      </template>
    </div>
  </div>
</template>

<script>
import {mapGetters} from 'vuex';

export default {
  computed: {

    ...mapGetters({
      currentOrganization: 'organizations/currentOrganization'
    }),
  }
}
</script>