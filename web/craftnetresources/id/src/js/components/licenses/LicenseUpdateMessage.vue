<template>
  <div>
    <template v-if="license && license.expirable && license.expiresOn">
      <template v-if="!license.expired">
        <template v-if="expiresSoon(license)">
          <template v-if="license.autoRenew">
            <p>This license will auto-renew in <span class="text-green">{{
                daysBeforeExpiry(license)
              }} days</span>.</p>
          </template>
          <template v-else>
            <p>This license will lose access to updates in
              <span class="text-orange">{{
                  daysBeforeExpiry(license)
                }} days</span>.</p>
          </template>
        </template>
        <template v-else>
          <template v-if="license.autoRenew">
            <p>This license will auto-renew on
              <strong>{{ license.expiresOn.date|moment('YYYY-MM-DD') }}</strong>.
            </p>
          </template>
          <template v-else>
            <p>This license will continue having access to updates until
              <strong>{{ license.expiresOn.date|moment('YYYY-MM-DD') }}</strong>.
            </p>
          </template>
        </template>
      </template>
      <template v-else>
        <p>This license has expired and doesn’t have access to updates
          anymore.</p>
      </template>
    </template>
    <template v-else>
      <p>This license will always have access to updates.</p>
    </template>
  </div>
</template>

<script>
import helpers from '../../mixins/helpers'

export default {
  mixins: [helpers],

  props: ['license'],
}
</script>
