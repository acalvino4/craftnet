module.exports = () => {
  return {
    module: {
      rules: [
        {
          test: /\.js$/,
          use: ['babel-loader'],
        },
      ]
    }
  }
}