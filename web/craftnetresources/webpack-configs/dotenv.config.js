const webpack = require("webpack")

module.exports = () => {
  return {
    plugins: [
      new webpack.DefinePlugin({
        "VUE_APP_NODE_ENV": JSON.stringify(process.env.NODE_ENV),
        "VUE_APP_BASE_URL": JSON.stringify(process.env.BASE_URL),
        "VUE_APP_CRAFT_API_ENDPOINT": JSON.stringify(process.env.VUE_APP_CRAFT_API_ENDPOINT),
        "VUE_APP_CRAFT_PLUGINS_URL": JSON.stringify(process.env.VUE_APP_CRAFT_PLUGINS_URL),
      }),
    ]
  }
}