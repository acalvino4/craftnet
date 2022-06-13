const prefixRE = /^VUE_APP_/

module.exports = () => {
  const env = []

  Object.keys(process.env).forEach(key => {
    if (prefixRE.test(key) || key === 'NODE_ENV') {
      env.push(key)
    }
  })

  return env
}