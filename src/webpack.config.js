const webpack = require('webpack');
const path = require('path');
const VersionFile = require('webpack-version-file');
const CopyPlugin = require("copy-webpack-plugin");

module.exports = {
    devtool: 'source-map',
    target: 'web', // nebo 'web'
    entry: {
        admin: './assets/admin/app.js',
    },
    output: {
        path: path.join(__dirname, 'assets/incore'),
        filename: '[name].bundle.js'
    },
    resolve: {
        extensions: ['.js', '.ts', '.json']
    },
    module: {
        rules: [
            {
                test: /\.ts$/,
                use: 'ts-loader',
                exclude: /node_modules/,
            },
            {
                test: /\.css$/,
                use: ['style-loader','css-loader']
            },
            {
                test: /\.scss$/,
                use: ['style-loader','css-loader','sass-loader']
            },
            {
                test: /\.(jpe?g|png|gif|svg)$/i,
                type: 'asset/resource',
            }
        ]
    },
    plugins: [
        new webpack.ProvidePlugin({
            naja: 'naja', // Globální dostupnost pro Naja
        }),
        new VersionFile({
            output: './assets/incore/version.txt',
            package: './package.json'
        }),
        function () {
            this.hooks.watchRun.tap('VersionFileWatch', (compiler) => {
                console.log('Regenerating version file...');
                new VersionFile({
                    output: './assets/incore/version.txt',
                    package: './package.json'
                }).apply(compiler);
            });
        },
    ]
};