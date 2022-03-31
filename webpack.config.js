const path = require('path')

const config = {
    mode: 'development',
    context: path.resolve(__dirname, './assets/js'),
    entry: {
        'service-worker': './service-worker.ts',
        register: './register.ts'
    },
    output: {
        path: path.resolve(__dirname, './templates'),
        filename: '[name].js'
    },
    module: {
        rules: [
            {
                test: /\.tsx?$/,
                exclude: /node_modules/,
                loader: 'ts-loader'
            }
        ]
    },
    resolve: {
        extensions: ['.ts', '.tsx', '.js', '.jsx', '.json'],
        alias: {
            '~': path.resolve(__dirname, 'assets/js')
        }
    }
}

module.exports = config
