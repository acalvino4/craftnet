<template>
  <div class="responsive-content">
    <table class="table">
      <thead>
      <tr>
        <th>License Key</th>
        <th>Edition</th>
        <th>Domain</th>
        <th>Notes</th>
        <th>Updates Until</th>
        <th>Auto Renew</th>
      </tr>
      </thead>
      <tbody>
      <template>
        <tr
          v-for="(license, key) in licenses"
          :key="key">
          <td>
            <code>
              <router-link
                v-if="license.key"
                :to="'/licenses/cms/'+license.id">
                {{ license.key.substr(0, 10) }}
              </router-link>

              <template v-else>
                {{ license.shortKey }}
              </template>
            </code>
          </td>
          <td>{{ $filters.capitalize(license.edition) }}</td>
          <td>{{ license.domain }}</td>
          <td>{{ license.notes }}</td>
          <td>
            <template v-if="license.expirable && license.expiresOn">
              <template v-if="!license.expired">
                <template v-if="expiresSoon(license)">
                                    <span class="text-yellow-800 dark:text-yellow-200">{{
                                        $filters.parseDate(license.expiresOn.date).toFormat('yyyy-MM-dd')
                                      }}</span>
                </template>
                <template v-else>
                  {{
                    $filters.parseDate(license.expiresOn.date).toFormat('yyyy-MM-dd')
                  }}
                </template>
              </template>
              <template v-else>
                <span class="text-light">Expired</span>
              </template>
            </template>
            <template v-else>
              Forever
            </template>
          </td>
          <td>
            <template v-if="license.expirable && license.expiresOn">
              <badge
                v-if="license.autoRenew == 1"
                type="success">
                Enabled
              </badge>
              <badge v-else>Disabled</badge>
            </template>
          </td>
        </tr>
      </template>
      </tbody>
    </table>
  </div>
</template>

<script>
import Badge from '../Badge'
import helpers from '../../mixins/helpers.js'

export default {
  mixins: [helpers],

  props: ['licenses'],

  components: {
    Badge
  },
}
</script>
