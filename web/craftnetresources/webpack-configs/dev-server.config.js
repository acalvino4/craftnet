const path = require("path");
const devServerSettings = require('../webpack-settings/dev-server.settings');

module.exports = () => {
  const static = path.resolve(__dirname, '../', 'dist')

  return {
    devServer: {
      host: devServerSettings.host,
      port: devServerSettings.port,
      server: devServerSettings.server,
      static,
      allowedHosts: "all",
      devMiddleware: {
        publicPath: devServerSettings.publicPath,
        writeToDisk: true,
      },
      headers: {"Access-Control-Allow-Origin": "*"},
      hot: true,
      client: {
        overlay: {
          errors: true,
          warnings: false,
        },
      },
    }
  }
}