const TerserPlugin = require("terser-webpack-plugin");
module.exports = () => {
    return {
        optimization: {
            minimize: true,
            minimizer: [new TerserPlugin()],
        },
    }
}