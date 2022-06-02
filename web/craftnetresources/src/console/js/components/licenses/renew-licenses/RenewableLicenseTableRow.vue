<template>
    <tr>
        <td>
            <input
                type="checkbox"
                :value="1"
                :disabled="(renewableLicense.type === 'cms-renewal' || !renewableLicense.key) ? true : false"
                :checked="renewableLicense.key ? isChecked : false"
                @input="$emit('checkLicense', {
                        $event: $event,
                        key: itemKey,
                    })"/>
        </td>
        <td :class="{'text-grey': !renewableLicense.key}">
            {{ renewableLicense.description }}
        </td>
        <td :class="{'text-grey': !renewableLicense.key}">{{
                this.renewableLicense.expiresOn ? $filters.parseDate(this.renewableLicense.expiresOn.date).toFormat('yyyy-MM-dd') : ''
            }}
        </td>
        <td :class="{'text-grey': !renewableLicense.key}">
            {{
                $filters.parseDate(renewableLicense.expiryDate).toFormat('yyyy-MM-dd')
            }}
        </td>
        <td>{{ $filters.currency(renewableLicense.amount) }}</td>
    </tr>
</template>

<script>
export default {
    props: ['itemKey', 'renewableLicense', 'isChecked', 'isDisabled'],
}
</script>