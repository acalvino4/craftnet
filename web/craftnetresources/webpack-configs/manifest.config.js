const {WebpackManifestPlugin} = require("webpack-manifest-plugin")

module.exports = () => {
    return {
        plugins: [
            new WebpackManifestPlugin({
                publicPath: '/',
                map: (file) => {
                    file.name = file.name.replace(/(\.[a-f0-9]{32})(\..*)$/, '$2')

                    return file
                },
            })
        ]
    }
}