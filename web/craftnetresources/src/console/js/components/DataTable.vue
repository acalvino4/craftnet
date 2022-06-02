<template>
    <div>
        <template v-if="filterable">
            <div class="mb-4">
                <input v-model="searchQuery" type="text" class="rounded text-sm border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 w-full"
                       :placeholder="options && options.texts && options.texts.filterPlaceholder ? options.texts.filterPlaceholder : 'Search…'"
                       @input="onSearch">
            </div>
        </template>
        <div class="overflow-x-scroll">
            <table class="w-full">
                <thead>
                <tr>
                    <th class="whitespace-nowrap" v-for="(column, columnKey) in columns"
                        :key="'data-table-' + columnKey">
                        <template v-if="options.headings && options.headings[column]">
                            {{ options.headings[column] }}
                        </template>
                        <template v-else>
                            {{ column }}
                        </template>
                    </th>
                </tr>
                </thead>
                <tbody>
                <template v-if="responseData && responseData.data">
                    <tr v-for="(dataItem, dataItemKey) in responseData.data" :key="'tr-' + dataItemKey">
                        <td v-for="(column, columnKey) in columns" :key="'response-data-table-' + columnKey">
                            <slot :name="column" :row="dataItem">
                                <template v-if="dataItem[column]">{{ dataItem[column] }}</template>
                            </slot>
                        </td>
                    </tr>
                </template>

                <template v-if="loading && !responseData">
                    <tr>
                        <td :colspan="columns.length">
                            Loading…
                        </td>
                    </tr>
                </template>

                <template v-if="responseData && responseData.data && responseData.data.length === 0">
                    <tr>
                        <td :colspan="columns.length">
                            No results.
                        </td>
                    </tr>
                </template>
                </tbody>
            </table>
        </div>

        <template v-if="responseData">
            <div class="mt-4 flex">
                <div class="flex-1 text-gray-500 text-sm">
                    {{ responseData.total }} results
                </div>

                <div class="flex items-center">
                    <template v-if="loading && responseData">
                        <div class="mr-4">
                            <spinner></spinner>
                        </div>
                    </template>

                    <template v-if="url">
                        <div class="flex space-x-3 relative">
                            <btn :disabled="currentPage <= 1" @click="goToPreviousPage">← Previous Page</btn>
                            <btn :disabled="currentPage >= responseData.last_page" @click="goToNextPage">Next Page →</btn>
                        </div>
                    </template>
                </div>
            </div>
        </template>
    </div>
</template>

<script>
/* global Craft */

import axios from 'axios';
import qs from 'qs';
import debounce from 'lodash.debounce';

export default {
    props: {
        url: String,
        columns: Array,
        options: Object,
        data: Array,
    },

    data() {
        return {
            loading: false,
            perPage: 3,
            currentPage: 1,
            responseData: null,
            searchQuery: '',
        }
    },

    computed: {
        requestData() {
            return {
                limit: this.perPage,
                page: this.currentPage,
                query: this.searchQuery,
            }

            // ascending: 1,
            // byColumn: 0,
        },
        filterable() {
            if (!this.url || (this.options && this.options.filterable == false)) {
                return false
            }

            return true
        },
    },

    methods: {
        onSearch: debounce(function () {
            this.fetchData()
        }, 500),

        fetchData() {
            this.loading = true

            return axios.post(this.url, qs.stringify(this.requestData), {
                headers: {
                    'X-CSRF-Token': Craft.csrfTokenValue,
                }
            })
                .then((response) => {
                    this.loading = false
                    this.responseData = response.data
                })
                .catch((error) => {
                    this.loading = false
                    throw error
                })
        },

        goToPage(page) {
            this.$router.push({
                query: {
                    page,
                }
            })

            this.currentPage = page
            this.fetchData()
        },

        goToPreviousPage() {
            let previousPage = this.currentPage - 1;

            if (previousPage < 1) {
                previousPage = 1
            }

            this.goToPage(previousPage)
        },

        goToNextPage() {
            let nextPage = this.currentPage + 1;

            if (nextPage > this.responseData.last_page) {
                nextPage = this.responseData.last_page
            }

            this.goToPage(nextPage)
        },
    },

    mounted() {
        if (this.url) {
            this.currentPage = this.$route.query.page || 1
            this.fetchData()
        } else if (this.data) {
            this.responseData = {
                data: this.data,
                total: this.data.length,
            }
        }
    }
}
</script>

<style>
th,
td {
    @apply px-4 py-3 border-b;
}

th {
    @apply border-t uppercase text-xs text-gray-500;
}

td {
}
</style>