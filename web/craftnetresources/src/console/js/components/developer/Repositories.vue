<template>
    <div>
        <textbox placeholder="Filter repositories" v-model="q"/>

        <list-group class="mt-6" v-if="filteredRepositories.length > 0">
            <list-group-item v-for="(repository, key) in filteredRepositories"
                             :key="key">
                <div class="flex items-center">
                    <div class="flex-1">
                        {{ repository.full_name }}
                    </div>
                    <div>
                        <spinner
                            v-if="isLoading(repository.html_url)"></spinner>

                        <template
                            v-if="!repositoryIsInUse(repository.html_url)">
                            <btn kind="primary" small
                                 @click="$emit('selectRepository', repository)">
                                Select
                            </btn>
                        </template>
                        <template v-else>
                            <btn
                                :disabled="repositoryIsInUse(repository.html_url )">
                                Already in use
                            </btn>
                        </template>
                    </div>
                </div>
            </list-group-item>
        </list-group>

        <p v-else>No repositories.</p>
    </div>
</template>

<script>
import filter from 'lodash/filter';
import includes from 'lodash/includes';
import {mapState, mapGetters} from 'vuex';
import ListGroup from '../ListGroup'
import ListGroupItem from '../ListGroupItem'

export default {
    props: ['appHandle', 'loadingRepository'],

    data() {
        return {
            q: ''
        };
    },

    components: {
        ListGroup,
        ListGroupItem,
    },

    computed: {
        ...mapState({
            apps: state => state.apps.apps,
        }),

        ...mapGetters({
            repositoryIsInUse: 'plugins/repositoryIsInUse',
        }),

        app() {
            return this.apps[this.appHandle];
        },

        repositories() {
            let unusedRepos = this.app.repositories.filter(r => !this.repositoryIsInUse(r.html_url));
            let inUseRepos = this.app.repositories.filter(r => this.repositoryIsInUse(r.html_url));

            return unusedRepos.concat(inUseRepos);
        },

        filteredRepositories() {
            let searchQuery = this.q;

            if (!searchQuery) {
                return this.repositories;
            }

            return filter(this.repositories, r => {
                if (r.full_name && includes(r.full_name.toLowerCase(), searchQuery.toLowerCase())) {
                    return true;
                }
            });
        }
    },

    methods: {
        /**
         * Is repository loading?
         *
         * @param repositoryUrl
         * @returns {boolean}
         */
        isLoading(repositoryUrl) {
            return this.loadingRepository === repositoryUrl;
        }
    }
}
</script>
