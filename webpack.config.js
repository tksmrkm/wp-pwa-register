const path = require('path')

const config = {
    mode: 'development',
    context: path.resolve(__dirname, './assets/js'),
    entry: {
        'service-worker': './service-worker.js',
        register: './register.js'
    },
    output: {
        path: path.resolve(__dirname, './templates'),
        filename: '[name].js'
    },
    module: {
        rules: [
            {
                test: /\.jsx?$/,
                exclude: /node_modules/,
                loader: 'babel-loader'
            }
        ]
    },
    resolve: {
        extensions: ['.js', '.jsx']
    }
}

module.exports = config
