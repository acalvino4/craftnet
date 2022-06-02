const {VueLoaderPlugin} = require('vue-loader')

module.exports = () => {
    return {
        module: {
            rules: [
                {
                    test: /\.vue$/,
                    loader: 'vue-loader'
                },
            ]
        },
        plugins: [
            new VueLoaderPlugin(),
        ]
    }
}