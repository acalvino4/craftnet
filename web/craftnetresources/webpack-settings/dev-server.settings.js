const server = process.env.DEV_SERVER_SERVER || "https";
const host = process.env.DEV_SERVER_HOST || "localhost";
const port = process.env.DEV_SERVER_PORT || "8083";
const scheme = server === 'https' ? "https" : "http";
const publicPath = process.env.DEV_SERVER_PUBLIC || `${scheme}://${host}:${port}/`;

module.exports = {
    server,
    host,
    port,
    publicPath
}