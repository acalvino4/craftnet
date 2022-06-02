<template>
    <transition name="fade">
        <div v-if="show" class="notification" :class="type">{{ message }}</div>
    </transition>
</template>

<script>
export default {
    props: ['type', 'message'],

    data() {
        return {
            show: false
        }
    },

    mounted() {
        this.show = true;

        let notificationDuration = 2000;

        if (this.type === 'error') {
            notificationDuration = notificationDuration * 4;
        }

        setTimeout(function() {
            this.show = false;
        }.bind(this), notificationDuration);
    }
}
</script>
