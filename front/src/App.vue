<template>
    <v-app>
        <v-snackbar v-model="snack.isShow">{{ snack.text }}</v-snackbar>
        <v-card>
            <v-card width="85%" style="margin:0 auto" elevation="0">
                <v-card-title style="word-break:break-word;padding-left:0">
                <span>
                    <span style="color:#46dcb5">KEEP</span>
                    The Only Chance
                </span>
                </v-card-title>
                <v-card-subtitle style="padding-left:0">
                    Random number generator using KEEP Random Beacon
                </v-card-subtitle>
            </v-card>
        </v-card>

        <v-card width="85%" style="margin:30px auto">
            <router-view :search="search"/>
        </v-card>
    </v-app>
</template>

<script>
export default {
    data() {
        return {
            config: global.config,
            search: '',
            snack: {isShow: false, text: ''},
        }
    },
    created() {
        this.config.totalNodes = '∞';
        this.$snack.listener = function (text) {
            this.snack.text = text;
            this.snack.isShow = false;
            this.snack.isShow = true;
        }.bind(this);
    },
}
</script>

<style lang="scss">
</style>
