<template>
  <div class="card mb-3">
    <div class="card-body">
      <h4>Activity</h4>

      <table
        v-if="history.length"
        class="table">
        <thead>
        <tr>
          <th>Notes</th>
          <th>Date</th>
        </tr>
        </thead>
        <tbody>
        <tr
          v-for="(item, key) in history"
          :key="key">
          <td>{{ item.note }}</td>
          <td>{{ item.timestamp }}</td>
        </tr>
        </tbody>
      </table>

      <p
        v-else
        class="text-secondary">No license history.</p>
    </div>
  </div>
</template>

<script>
export default {
  props: ['history']
}
</script>