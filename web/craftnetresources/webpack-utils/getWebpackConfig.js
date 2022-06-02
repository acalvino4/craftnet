const {merge} = require('webpack-merge')

const getWebpackConfig = (name) => require(`../webpack-configs/${name}.config`)()

const webpackConfigs = (names, getWebpackConfig) => {
    let config = {};
    names.forEach((name) => config = merge(config, getWebpackConfig(name)));

    return config;
}

const getWebpackConfigs = (...names) => webpackConfigs(names, getWebpackConfig);

module.exports = {
    getWebpackConfigs,
}