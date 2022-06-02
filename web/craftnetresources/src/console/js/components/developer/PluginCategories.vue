<template>
    <div v-if="pluginDraft">
        <field label="Categories"
               :instructions="'Pick up to '+maxCategories+' categories. ('+ pluginDraftCategoryIds.length +'/'+maxCategories+' selected)'">
            <template v-if="loading">
                <spinner></spinner>
            </template>

            <template v-else>
                <div>
                    <draggable v-model="pluginDraftCategoryIds"
                               item-key="index">
                        <template #item="{element, index}">
                            <div
                                class="alert inline-block clearfix mb-3 mr-2 px-3 py-2"
                                :key="'selected-categories-' + index">
                                <div class="flex">
                                    <div>
                                        {{ selectedCategoryById(element).title }}
                                    </div>
                                    <div class="ml-3 mt-1">
                                        <a @click.prevent="unselectCategory(element)">
                                            <icon icon="x"
                                                  class="text-red-500"/>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </draggable>
                </div>

                <div class="clearfix"></div>

                <div>
                    <div class="inline-block"
                         v-for="(category, key) in availableCategories"
                         :key="'available-category-' + key">
                        <btn class="mb-2 mr-2"
                             :disabled="pluginDraftCategoryIds.length >= maxCategories"
                             outline @click="selectCategory(category.id)">
                            <icon icon="plus"/>
                            {{ category.title }}
                        </btn>
                    </div>
                </div>
            </template>
        </field>
    </div>
</template>

<script>
import {mapState, mapActions} from 'vuex'
import draggable from 'vuedraggable'

export default {
    components: {
        draggable
    },

    props: ['pluginDraft'],

    data() {
        return {
            loading: false,
            maxCategories: 3,
        }
    },

    computed: {
        ...mapState({
            categories: state => state.pluginStore.categories,
        }),

        selectedCategories() {
            let categories = []

            this.pluginDraftCategoryIds.forEach(categoryId => {
                const category = this.categories.find(c => c.id == categoryId)
                categories.push(category)
            })

            return categories
        },

        selectedCategoryById() {
            return (categoryId) => {
                return this.selectedCategories.find(c => c.id === categoryId)
            }
        },

        availableCategories() {
            return this.categories.filter(category => {
                return !this.pluginDraftCategoryIds.find(categoryId => categoryId == category.id)
            })
        },

        categoryOptions() {
            let options = []

            this.categories.forEach(category => {
                let checked = this.pluginDraftCategoryIds.find(categoryId => categoryId == category.id)

                let option = {
                    label: category.title,
                    value: category.id,
                    checked: checked,
                }

                options.push(option)
            })

            return options
        },

        pluginDraftCategoryIds: {
            get() {
                return this.pluginDraft.categoryIds
            },

            set(categoryIds) {
                const pluginDraft = JSON.parse(JSON.stringify(this.pluginDraft))
                pluginDraft.categoryIds = categoryIds
                this.$emit('update:pluginDraft', pluginDraft)
            }
        }
    },

    methods: {
        ...mapActions({
            getCoreData: 'pluginStore/getCoreData',
        }),

        /**
         * Select category.
         *
         * @param categoryId
         */
        selectCategory(categoryId) {
            if (this.pluginDraftCategoryIds.length < this.maxCategories) {
                const exists = this.pluginDraftCategoryIds.find(catId => catId == categoryId)

                if (!exists) {
                    this.pluginDraftCategoryIds.push(categoryId)
                }
            }
        },

        /**
         * Unselect category.
         *
         * @param categoryId
         */
        unselectCategory(categoryId) {
            const i = this.pluginDraftCategoryIds.indexOf(categoryId)

            if (i !== -1) {
                this.pluginDraftCategoryIds.splice(i, 1)
            }
        },
    },

    mounted() {
        this.loading = true

        this.getCoreData()
            .then(() => {
                this.loading = false
            })
    }
}
</script>
