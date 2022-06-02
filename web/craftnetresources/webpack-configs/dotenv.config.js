const webpack = require("webpack")

module.exports = () => {
    return {
        plugins: [

            new webpack.DefinePlugin({
                "process.env.NODE_ENV": JSON.stringify(process.env.NODE_ENV),
                "process.env.BASE_URL": JSON.stringify(process.env.BASE_URL),
                "process.env.VUE_APP_CRAFT_API_ENDPOINT": JSON.stringify(process.env.VUE_APP_CRAFT_API_ENDPOINT),
            }),
        ]
    }
}