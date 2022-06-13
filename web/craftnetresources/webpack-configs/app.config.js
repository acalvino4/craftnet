const path = require("path")
const isDevServerRunning = require("../webpack-utils/isDevServerRunning")
const devServerSettings = require('../webpack-settings/dev-server.settings');

// Load environmnent variables
const dotenvPath = path.resolve(__dirname, '../', '.env')
require('dotenv').config({path: dotenvPath})

module.exports = () => {
  return {
    mode: process.env.NODE_ENV,
    devtool: 'source-map',
    entry: {
      console: path.resolve(__dirname, '../', 'src/console/js/console.js'),
      oauthAuthorization: path.resolve(__dirname, '../', 'src/oauth-authorization/js/oauth-authorization.js')
    },
    output: {
      path: path.resolve(__dirname, '../', 'dist'),
      filename: isDevServerRunning() ? 'js/[name].js' : 'js/[name].[contenthash].js',
      publicPath: isDevServerRunning() ? devServerSettings.publicPath : '/craftnetresources/dist/',
    },
    resolve: {
      alias: {
        "@": path.resolve(__dirname, '../src/'),
      },
      extensions: ['.vue', '.js']
    },
  }
}