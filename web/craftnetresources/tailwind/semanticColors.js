const colors = require("tailwindcss/colors");

module.exports = {
    backgroundColor: {
        primary: {
            light: colors.white,
            dark: colors.gray[900],
        },
    },
    borderColor: {
        separator: {
            light: colors.gray[200],
            dark: colors.gray[700],
        },
        DEFAULT: {
            light: colors.gray[200],
            dark: colors.gray[700],
        }
    },
    textColor: {
        'light': {
            light: colors['gray'][500],
            dark: colors['gray'][500]
        },
    },
    boxShadowColor: {
        'sm': {light: 'rgba(0, 0, 0, 0.05)', dark: 'rgba(0, 0, 0, 0.05)'},
        'default-1': {light: 'rgba(0, 0, 0, 0.1)', dark: 'rgba(0, 0, 0, 0.1)'},
        'default-2': {light: 'rgba(0, 0, 0, 0.06)', dark: 'rgba(0, 0, 0, 0.06)'},
        'md-1': {light: 'rgba(0, 0, 0, 0.1)', dark: 'rgba(0, 0, 0, 0.1)'},
        'md-2': {light: 'rgba(0, 0, 0, 0.06)', dark: 'rgba(0, 0, 0, 0.06)'},
        'lg-1': {light: 'rgba(0, 0, 0, 0.1)', dark: 'rgba(0, 0, 0, 0.1)'},
        'lg-2': {light: 'rgba(0, 0, 0, 0.05)', dark: 'rgba(0, 0, 0, 0.05)'},
        'xl-1': {light: 'rgba(0, 0, 0, 0.1)', dark: 'rgba(0, 0, 0, 0.1)'},
        'xl-2': {light: 'rgba(0, 0, 0, 0.04)', dark: 'rgba(0, 0, 0, 0.04)'},
        '2xl': {light: 'rgba(0, 0, 0, 0.25)', dark: 'rgba(0, 0, 0, 0.25)'},
        'shadow-inner': {light: 'rgba(0, 0, 0, 0.06)', dark: 'rgba(0, 0, 0, 0.06)'},
    }
}