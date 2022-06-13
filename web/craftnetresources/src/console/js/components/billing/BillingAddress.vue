<template>
  <div>
    <ul
      v-if="address"
      class="address-data">
      <li v-if="address.firstName || address.lastName">
        <strong>
          <template v-if="address.attention">Att: {{
              address.attention
            }}<br></template>
          <template v-if="address.title">{{
              address.title
            }}
          </template>
          <template v-if="address.firstName">{{
              address.firstName
            }}
          </template>
          <template v-if="address.lastName">{{
              address.lastName
            }}
          </template>
        </strong>
      </li>
      <li v-if="address.businessName || address.businessTaxId">

        <strong>
          <template v-if="address.businessName">
            {{ address.businessName }}
          </template>
          <template v-if="address.businessTaxId">-
            {{ address.businessTaxId }}
          </template>
        </strong>
      </li>
      <li v-if="address.address1">{{ address.address1 }}</li>
      <li v-if="address.address2">{{ address.address2 }}</li>
      <li v-if="address.zipCode || address.city">
        <template v-if="address.zipCode">{{
            address.zipCode
          }}
        </template>
        <template v-if="address.city">{{ address.city }}</template>
      </li>
      <li v-if="address.countryText">{{ address.countryText }}</li>
      <li v-if="address.stateText">{{ address.stateText }}</li>
      <li v-if="address.phone">{{ address.phone }}</li>
      <li v-if="address.alternativePhone">{{
          address.alternativePhone
        }}
      </li>
    </ul>
  </div>
</template>

<script>
export default {
  props: ['address'],
}
</script>
