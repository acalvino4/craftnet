module.exports = () => {
    if (process.env.NODE_ENV !== 'development') {
        return false
    }

    return true
}