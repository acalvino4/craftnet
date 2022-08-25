<template>
  <div>
    <h1 class="m-0">Orders</h1>
    <div class="mt-6 space-y-6">
      <pane>
        <h2>Pending Approval</h2>
        <pending-approval-orders class="mt-4" />
      </pane>

      <pane>
        <h2>Organization Orders</h2>
        <organization-orders class="mt-4" />
      </pane>
    </div>
  </div>
</template>

<script>
import OrganizationOrders from '../../../components/orders/OrganizationOrders';
import PendingApprovalOrders from '../../../components/orders/PendingApprovalOrders';

export default {
  components: {PendingApprovalOrders, OrganizationOrders},
}
</script>