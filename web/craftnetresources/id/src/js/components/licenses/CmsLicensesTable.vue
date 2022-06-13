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
          <td>{{ license.edition|capitalize }}</td>
          <td>{{ license.domain }}</td>
          <td>{{ license.notes }}</td>
          <td>
            <template v-if="license.expirable && license.expiresOn">
              <template v-if="!license.expired">
                <template v-if="expiresSoon(license)">
                  <span class="text-orange">{{
                      license.expiresOn.date|moment('YYYY-MM-DD')
                    }}</span>
                </template>
                <template v-else>
                  {{ license.expiresOn.date|moment('YYYY-MM-DD') }}
                </template>
              </template>
              <template v-else>
                <span class="text-grey-dark">Expired</span>
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
                type="success">Enabled
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
import helpers from '../../mixins/helpers'

export default {
  mixins: [helpers],

  props: ['licenses'],

  components: {
    Badge
  },
}
</script>
