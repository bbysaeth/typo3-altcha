const path = require('path');

module.exports = {
  entry: './Resources/Public/JavaScript/index.js',
  output: {
    filename: 'altcha.js',
    path: path.resolve(__dirname, 'Resources/Public/JavaScript/dist'),
  },
  mode: 'production',
};