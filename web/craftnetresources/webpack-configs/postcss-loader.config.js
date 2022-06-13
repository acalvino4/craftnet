const path = require("path")
const MiniCssExtractPlugin = require("mini-css-extract-plugin")
const TailwindCss = require('tailwindcss');

module.exports = () => {
  return {
    module: {
      rules: [
        {
          test: /\.(pcss|css|scss)$/,
          use: [
            MiniCssExtractPlugin.loader,
            {loader: 'css-loader', options: {importLoaders: 2}},
            {
              loader: 'postcss-loader',
              options: {
                postcssOptions: {
                  // path: postCssConfig
                  path: path.resolve(__dirname),
                  plugins: [
                    [
                      'postcss-import', {
                      path: ['../node_modules'],
                    }
                    ],
                    require('tailwindcss/nesting'),
                    TailwindCss(path.resolve(__dirname, '../tailwind.config.js')),
                    'autoprefixer',
                  ]
                },
              }
            },
            {loader: 'sass-loader'}
          ]
        }
      ]
    },
  }
}