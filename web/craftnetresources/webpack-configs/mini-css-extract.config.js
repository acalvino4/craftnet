const MiniCssExtractPlugin = require("mini-css-extract-plugin")
const isDevServerRunning = require("../webpack-utils/isDevServerRunning")

module.exports = () => {
  const plugins = []

  if (!isDevServerRunning()) {
    plugins.push(new MiniCssExtractPlugin({
      filename: 'css/[name].[contenthash].css',
      chunkFilename: 'css/[name].[contenthash].css',
    }))
  } else {
    plugins.push(new MiniCssExtractPlugin({
      filename: 'css/[name].css',
      chunkFilename: 'css/[name].css',
    }))
  }

  return {
    plugins,
  }
}