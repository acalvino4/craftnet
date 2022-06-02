<template>
    <div class="border p-8">
        <div class="text-center lg:flex-1">
            <div class="support-plan flex flex-col justify-between">
                <div class="details">
                    <div class="plan-icon">
                        <icon :icon="plan.icon" class="w-16 h-16 text-blue-500"></icon>
                    </div>

                    <h2 class="mb-6">{{ plan.name }}</h2>

                    <ul class="feature-list">
                        <li v-for="(feature, featureKey) in plan.features"
                            :key="'features-'+featureKey">
                            <icon icon="check"
                                  class="text-gray-500 w-4 h-4 mt-1 mr-4"/>
                            <span>{{ feature }}</span>
                        </li>
                    </ul>
                </div>

                <div class="actions py-4">
                    <div v-if="plan.price > 0" class="my-4">
                        <h3 class="text-3xl">${{ plan.price }}</h3>
                        <p class="text-grey">per seat, monthly</p>
                    </div>

                    <div v-if="plan.price > 0" class="mt-4">
                        <template v-if="subscriptionInfoPlan">
                            <template
                                v-if="subscriptionInfoSubscriptionData.status === 'inactive'">
                                <btn kind="primary"
                                     @click="$emit('selectPlan', plan)">Select
                                    this plan
                                </btn>
                            </template>
                            <template
                                v-else-if="subscriptionInfoSubscriptionData.status === 'active'">
                                <btn kind="primary" :disabled="true">Current
                                    plan
                                </btn>
                                <p class="mt-4">
                                    Next billing date:
                                    {{ subscriptionInfoSubscriptionData.nextBillingDate }}
                                </p>
                                <div class="mt-2">
                                    <a @click.prevent="$emit('cancelSubscription', subscriptionInfoSubscriptionData.uid)">Cancel
                                        subscription</a>
                                </div>
                            </template>
                            <template
                                v-else-if="subscriptionInfoSubscriptionData.status === 'expiring'">
                                <btn kind="primary"
                                     @click="$emit('reactivateSubscription', subscriptionInfoSubscriptionData.uid)">
                                    Reactivate
                                </btn>
                                <p class="mt-4">Expires on
                                    {{ subscriptionInfoSubscriptionData.expiringDate }}.</p>
                            </template>
                            <template
                                v-else-if="subscriptionInfoSubscriptionData.status === 'upcoming'">
                                <p class="mt-4">
                                    Starts on
                                    {{ subscriptionInfoSubscriptionData.startingDate }}.
                                </p>

                                <div class="mt-2">
                                    <a @click.prevent="$emit('cancelSubscription', subscriptionInfoSubscriptionData.uid)">Cancel
                                        subscription</a>
                                </div>
                            </template>
                        </template>
                    </div>

                    <div v-if="plan.handle === 'basic'">
                        <p class="mb-0 text-light"><em>Comes standard with Craft
                            Pro.</em></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import helpers from '../../mixins/helpers.js'

export default {
    props: {
        plan: Object,
    },

    mixins: [helpers],

    computed: {
        subscriptionInfoPlan() {
            return this.$store.getters['developerSupport/subscriptionInfoPlan'](this.plan.handle)
        },

        subscriptionInfoSubscriptionData() {
            return this.$store.getters['developerSupport/subscriptionInfoSubscriptionData'](this.plan.handle)
        }
    },
}
</script>