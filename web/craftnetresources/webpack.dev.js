const {getWebpackConfigs} = require("./webpack-utils/getWebpackConfig")

// environment
process.env.NODE_ENV = process.env.NODE_ENV || 'development'

module.exports = getWebpackConfigs(
    'app',
    'dotenv',
    'copy-images',
    'code-splitting',
    'dev-server',
    'file-loader',
    'babel-loader',
    'postcss-loader',
    'vue-loader',
    'mini-css-extract',
    'manifest',
)
