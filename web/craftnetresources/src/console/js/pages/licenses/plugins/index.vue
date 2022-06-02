<template>
    <div>
        <page-header>
            <h1>Plugins</h1>
        </page-header>

        <data-table :url="apiUrl" :columns="vtColumns" :options="vtOptions">
            <template v-slot:licenseKey="props">
                <code>
                    <router-link v-if="props.row.key"
                                 :to="'/licenses/plugins/'+props.row.id">
                        {{ props.row.key.substr(0, 4) }}
                    </router-link>

                    <template v-else>
                        {{ props.row.shortKey }}
                    </template>
                </code>
            </template>
            <template v-slot:plugin="props">
                {{ props.row.plugin.name }}

                <template v-if="props.row.plugin.hasMultipleEditions">
                    <edition-badge class="ml-2 inline-block">
                        {{ props.row.edition.name }}
                    </edition-badge>
                </template>
            </template>

            <template v-slot:notes="props">
                {{ props.row.notes }}
            </template>

            <template v-slot:cmsLicense="props">
                <template v-if="props.row.cmsLicense">
                    <code>
                        <router-link v-if="props.row.cmsLicense.key"
                                     :to="'/licenses/cms/'+props.row.cmsLicenseId">
                            {{ props.row.cmsLicense.key.substr(0, 10) }}
                        </router-link>
                        <template v-else>{{
                                props.row.cmsLicense.shortKey
                            }}
                        </template>
                    </code>
                </template>

                <template v-else>
                    —
                </template>
            </template>

            <template v-slot:expiresOn="props">
                <template v-if="props.row.expirable && props.row.expiresOn">
                    <template v-if="!props.row.expired">
                        <template v-if="expiresSoon(props.row)">
                            <span class="text-yellow-800 dark:text-yellow-200">{{
                                    $filters.parseDate(props.row.expiresOn.date).toFormat('yyyy-MM-dd')
                                }}</span>
                        </template>
                        <template v-else>
                            {{
                                $filters.parseDate(props.row.expiresOn.date).toFormat('yyyy-MM-dd')
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
            </template>

            <template v-slot:autoRenew="props">
                <template v-if="props.row.expirable && props.row.expiresOn">
                    <badge v-if="props.row.autoRenew == 1" type="success">
                        Enabled
                    </badge>
                    <badge v-else>Disabled</badge>
                </template>
            </template>
        </data-table>
    </div>
</template>

<script>
/* global Craft */

import EditionBadge from '../../../components/EditionBadge'
import helpers from '../../../mixins/helpers.js'
import PageHeader from '@/console/js/components/PageHeader'
import DataTable from '@/console/js/components/DataTable';

export default {
    mixins: [helpers],

    components: {
        DataTable,
        EditionBadge,
        PageHeader,
    },

    data() {
        return {
            vtColumns: ['licenseKey', 'plugin', 'notes', 'cmsLicense', 'expiresOn', 'autoRenew'],
            vtOptions: {
                filterable: false,
                headings: {
                    'licenseKey': "License Key",
                    'cmsLicense': "CMS License",
                    'expiresOn': "Expires On",
                    'autoRenew': "Auto Renew",
                }
            },
        }
    },

    computed: {
        apiUrl() {
            return Craft.actionUrl + '/craftnet/console/plugin-licenses/get-licenses'
        },
    },

    mounted() {
        this.$store.dispatch('pluginLicenses/getExpiringPluginLicensesTotal')
    }
}
</script>
