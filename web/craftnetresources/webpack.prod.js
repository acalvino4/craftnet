const {getWebpackConfigs} = require("./webpack-utils/getWebpackConfig")

// environment
process.env.NODE_ENV = process.env.NODE_ENV || 'production'

module.exports = getWebpackConfigs(
  'app',
  'clean',
  'dotenv',
  'copy-images',
  'code-splitting',
  'file-loader',
  'babel-loader',
  'postcss-loader',
  'vue-loader',
  'mini-css-extract',
  'minimize',
  'manifest',
)
