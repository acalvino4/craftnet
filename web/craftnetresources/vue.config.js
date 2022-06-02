const path = require('path');

const ManifestPlugin = require('webpack-manifest-plugin')

module.exports = {
    devServer: {
        headers: { "Access-Control-Allow-Origin": "*" },
        https: true,
        port: process.env.DEV_SERVER_PORT,

        // Fix bug caused by webpack-dev-server 3.1.11.
        // https://github.com/vuejs/vue-cli/issues/3173#issuecomment-449573901
        disableHostCheck: true,

        public: process.env.DEV_BASE_URL,
        watchContentBase: true,
        watchOptions: {
            poll: 1000,
            ignored: /node_modules/,
        }
    },
    publicPath: process.env.NODE_ENV === 'production' ? '/craftnetresources/dist/' : process.env.DEV_BASE_URL,
    configureWebpack: {
        plugins: [
            new ManifestPlugin({
                publicPath: process.env.NODE_ENV === 'production' ? '/' : process.env.DEV_BASE_URL
            }),
        ],
        resolve: {
            alias: {
                // Fix double vue instance issue
                // https://github.com/vuejs/vue-cli/issues/4271#issuecomment-585299391
                vue$: path.resolve('./node_modules/vue/dist/vue.runtime.esm-bundler.js'),
            },
        }
    },
    chainWebpack: config => {
        // Remove the standard entry point
        config.entryPoints.delete('app')

        // Add entry points
        config
            .entry('console')
            .add('./src/console/js/console.js')
            .end()
            .entry('oauth-authorization')
            .add('./src/oauth-authorization/js/oauth-authorization.js')
            .end()
    },
}
