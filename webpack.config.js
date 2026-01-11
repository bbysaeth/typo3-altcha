const path = require("path");

module.exports = {
  entry: "./Resources/Public/JavaScript/index.js",
  output: {
    filename: "altcha.js",
    path: path.resolve(__dirname, "Resources/Public/JavaScript/dist"),
  },
  mode: "production",
  resolve: {
    alias: {
      "altcha/i18n": path.resolve(
        __dirname,
        "node_modules/altcha/dist/altcha.i18n.js"
      ),
      altcha: path.resolve(
        __dirname,
        "node_modules/altcha/dist_external/altcha.js"
      ),
    },
  },
};
