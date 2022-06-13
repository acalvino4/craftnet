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

module.exports = tailwindPlugin.withOptions(
  // Plugin function
  function(pluginOptions) {
    return function(options) {
      const {addBase} = options

      const lightColors = {};

      for (let colorSetKey in pluginOptions.semanticColors) {
        for (let colorKey in pluginOptions.semanticColors[colorSetKey]) {
          const inputColor = pluginOptions.semanticColors[colorSetKey][colorKey].light
          lightColors[`--console-color-${colorSetKey.toLowerCase()}-${colorKey}`] = parseColor(inputColor)
        }
      }

      const darkColors = {};

      for (let colorSetKey in pluginOptions.semanticColors) {
        for (let colorKey in pluginOptions.semanticColors[colorSetKey]) {
          const inputColor = pluginOptions.semanticColors[colorSetKey][colorKey].dark
          darkColors[`--console-color-${colorSetKey.toLowerCase()}-${colorKey}`] = parseColor(inputColor);
        }
      }

      // console.log('lightColors', lightColors);

      addBase({
        // `light` color scheme
        'body': {
          ...lightColors,
          '--console-color-text-lightold': '255 196 2',
        },

        // `dark` color scheme
        '@media (prefers-color-scheme: dark)': {
          'body': {
            ...darkColors,
            '--console-color-text-lightold': '220 208 192',
          },
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