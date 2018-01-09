const webpack = require('webpack');
const constants = require('./const');

const config = {
    context: constants.context,
    entry: {
        'service-worker': './service-worker.js',
        register: './register.js'
    },
    output: {
        path: constants.output,
        filename: '[name].js'
    },
    module: {
        loaders: [{
            test: /\.js$/,
            exclude: /node_modules/,
            loader: 'babel-loader'
        }]
    },
    plugins: []
};

module.exports = config;
