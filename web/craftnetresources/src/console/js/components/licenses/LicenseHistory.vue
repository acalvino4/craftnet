<template>
    <pane class="activity">
        <h2>Activity</h2>

        <table v-if="history.length" class="table mt-4">
            <thead>
            <tr>
                <th>Notes</th>
                <th>Date</th>
            </tr>
            </thead>
            <tbody>
            <tr v-for="(item, key) in history" :key="key">
                <td>{{ item.note }}</td>
                <td>{{ item.timestamp }}</td>
            </tr>
            </tbody>
        </table>

        <p v-else class="text-light">No license history.</p>
    </pane>
</template>

<script>
export default {
    props: ['history']
}
</script>