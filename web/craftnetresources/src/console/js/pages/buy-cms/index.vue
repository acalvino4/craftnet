<template>
    <div>
        <page-header>
            <div>
                <h1>Buy Craft CMS</h1>
                <p class="mt-2">Adding Craft CMS {{ edition }} edition to your
                    cart…</p>
            </div>
        </page-header>
        <spinner v-if="loading"></spinner>
    </div>
</template>

<script>
import PageHeader from '@/console/js/components/PageHeader';

export default {
    components: {PageHeader},
    data() {
        return {
            loading: true,
            type: 'cms-edition',
        }
    },

    computed: {
        edition() {
            return this.$route.params.edition
        }
    },

    methods: {
        addToCart() {
            this.loading = true

            const item = {
                type: 'cms-edition',
                edition: this.edition,
                autoRenew: false,
            }

            this.$store.dispatch('cart/addToCart', [item])
                .then(() => {
                    this.loading = false
                    this.$store.dispatch('app/displayNotice', 'Craft CMS license added your cart.')
                    this.$router.push({path: '/cart'})
                })
        }
    },

    mounted() {
        this.addToCart()
    }
}
</script>
