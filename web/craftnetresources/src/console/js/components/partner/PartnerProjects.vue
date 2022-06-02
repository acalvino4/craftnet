<template>
    <pane class="mt-4">
        <template v-slot:header>
            <div class="sm:flex sm:justify-between sm:items-center">
                <div class="sm:mr-6">
                    <h2 class="mb-0 text-lg">Projects</h2>

                    <p class="mb-0 text-light text-sm">
                        Five projects are required, up to eight will be
                        displayed.
                        Each must have a public URL for a case study or website.
                    </p>
                </div>

                <template v-if="partner.projects.length < 8">
                    <btn class="mt-3 sm:mt-0" @click="onAddProjectClick">
                        <icon icon="plus"/>
                        Add a Project
                    </btn>
                </template>
            </div>
        </template>

        <template v-slot:default v-if="draftProjects.length > 0">
            <div class="-my-6">
                <draggable v-model="draftProjects" @update="onSave"
                           item-key="id">
                    <template #item="{element, index}">
                        <div class="py-6">
                            <partner-project
                                :project="element"
                                :key="index"
                                :index="index"
                                :edit-index="editIndex"
                                :request-pending="requestPending"
                                :errors="errors[index]"
                                @cancel="onCancel"
                                @delete="onDelete"
                                @edit="onEdit"
                                @save="onSave"
                            ></partner-project>
                        </div>
                    </template>
                </draggable>
            </div>
        </template>
    </pane>
</template>

<script>
import helpers from '../../mixins/helpers.js'
import PartnerProject from './PartnerProject'
import draggable from 'vuedraggable'

export default {
    props: ['partner'],

    mixins: [helpers],

    components: {
        PartnerProject,
        draggable,
    },

    data() {
        return {
            errors: [],
            draftProjects: [],
            draftProjectProps: [
                'id',
                'name',
                'role',
                'url',
                'linkType',
                'withCraftCommerce',
                'screenshots',
            ],
            editIndex: null,
            requestPending: false,
        }
    },

    methods: {
        onAddProjectClick() {
            this.draftProjects.push({
                id: 'new',
                name: '',
                role: '',
                url: '',
                screenshots: [],
            })
        },

        cloneProjects(projects) {
            let clone = []

            for (let i = 0; i < projects.length; i++) {
                const project = this.partner.projects[i]
                clone.push(this.simpleClone(project, this.draftProjectProps))
            }

            return clone
        },

        onCancel() {
            // reset
            this.setDraftProjects()
            this.editIndex = null
        },

        onDelete(index) {
            if (this.draftProjects.length === 1) {
                this.$store.dispatch('app/displayError', 'Must have at least one project');
                return;
            }

            // we can't splice `draftProjects` or the modal for the
            // spliced out project will disappear
            let projects = this.cloneProjects(this.draftProjects)
            projects.splice(index, 1)
            this.save(projects)
        },

        onEdit(index) {
            this.errors = []
            this.editIndex = index
        },

        onSave(context) {
            this.draftProjects[this.editIndex] = context.project
            this.save(this.draftProjects);
        },

        save(projects) {
            this.requestPending = true

            this.$store.dispatch('patchPartnerProjects', projects)
                .then(response => {
                    this.requestPending = false

                    if (!response.data.success) {
                        this.errors = response.data.errors.projects
                        this.$store.dispatch('app/displayError', 'Validation errors')
                    } else {
                        this.setDraftProjects();
                        this.$store.dispatch('app/displayNotice', 'Updated')
                        this.editIndex = null
                    }
                })
                .catch(errorMessage => {
                    this.$store.dispatch('app/displayError', errorMessage)
                    this.requestPending = false
                })
        },

        setDraftProjects() {
            this.draftProjects = this.cloneProjects(this.partner.projects)
        }
    },

    mounted() {
        this.setDraftProjects()
    },
}
</script>
