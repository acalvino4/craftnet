const tailwindPlugin = require('tailwindcss/plugin')
const {colord} = require('colord')

function withOpacityValue(variable) {
  return ({opacityValue}) => {
    if (opacityValue === undefined) {
      return `rgb(var(${variable}))`
    }
    return `rgb(var(${variable}) / ${opacityValue})`
  }
}

function parseColor(inputColor) {
  const parsedColor = colord(inputColor)

  if (parsedColor.rgba.a !== 1) {
    return inputColor;
  }

  return `${parsedColor.rgba.r} ${parsedColor.rgba.g} ${parsedColor.rgba.b}`;
}

function getParsedColors(pluginOptions, theme) {
  const colors = {};

  for (let colorSetKey in pluginOptions.semanticColors) {
    for (let colorKey in pluginOptions.semanticColors[colorSetKey]) {
      const inputColor = pluginOptions.semanticColors[colorSetKey][colorKey][theme]
      if (inputColor) {
        colors[`--console-color-${colorSetKey.toLowerCase()}-${colorKey}`] = parseColor(inputColor)
      }
    }
  }

  return colors
}

module.exports = tailwindPlugin.withOptions(
  // Plugin function
  function(pluginOptions) {
    return function(options) {
      const {addBase} = options

      const lightColors = getParsedColors(pluginOptions, 'light')
      const lightContrastColors = getParsedColors(pluginOptions, 'lightContrast')
      const darkColors = getParsedColors(pluginOptions, 'dark')
      const darkContrastColors = getParsedColors(pluginOptions, 'darkContrast')

      addBase({
        // light
        'body': {
          ...lightColors,
          '--console-color-text-lightold': '255 196 2',
        },

        // dark
        '@media (prefers-color-scheme: dark)': {
          'body': {
            ...darkColors,
            '--console-color-text-lightold': '220 208 192',
          },
        },

        // light + contrast
        '@media (prefers-color-scheme: light) and (prefers-contrast: more)': {
          'body': lightContrastColors,
        },

        // dark + contrast
        '@media (prefers-color-scheme: dark) and (prefers-contrast: more)': {
          'body': darkContrastColors,
        },
      })
    }
  },

  // Config function
  function(pluginOptions = {}) {
    const extendedColors = {}

    for (let colorSetKey in pluginOptions.semanticColors) {
      extendedColors[colorSetKey] = {}

      for (let colorKey in pluginOptions.semanticColors[colorSetKey]) {
        const inputColor = pluginOptions.semanticColors[colorSetKey][colorKey].light
        const parsedColor = parseColor(inputColor)

        if (inputColor === parsedColor) {
          extendedColors[colorSetKey][colorKey] = `var(--console-color-${colorSetKey.toLowerCase()}-${colorKey})`
        } else {
          extendedColors[colorSetKey][colorKey] = withOpacityValue(`--console-color-${colorSetKey.toLowerCase()}-${colorKey}`)
        }
      }
    }

    // console.log('---------extendedColors', extendedColors);

    return {
      theme: {
        extend: {
          ...extendedColors,
          // textColor: {
          //
          //     lightold: withOpacityValue('--console-color-text-lightold')
          // }
        }
      },
    }
  }
)