const semanticColors = require('./tailwind/semanticColors')

module.exports = {
  content: [
    './src/console/js/**/*.{js,jsx,ts,tsx,vue}',
    './src/oauth-authorization/js/**/*.{js,jsx,ts,tsx,vue}',
    './src/common/ui/**/*.{js,jsx,ts,tsx,vue}',
    '../../../templates/**/*.{html,twig}'
  ],
  plugins: [
    require('@tailwindcss/forms'),
    require('@pixelandtonic/tailwindcss-semantic-colors')({
      semanticColors,
    }),
  ],
  theme: {
    // Change the default ring color
    borderColor: (theme) => ({
      DEFAULT: theme('colors.blue.400'),
      ...theme('colors'),
    }),
    extend: {
      padding: {
        '18': '4.5rem'
      }
    }
  }
}