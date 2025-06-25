const path = require('path');
const glob = require('glob');
const MiniCssExtractPlugin = require('mini-css-extract-plugin');
const CssMinimizerPlugin = require('css-minimizer-webpack-plugin');
const TerserPlugin = require('terser-webpack-plugin');
const fs = require('fs');

function getEntryPoints() {
    const entries = {};

    // Helper function to check if a file is not empty
    function isNotEmptyFile(filePath) {
        return fs.statSync(filePath).size > 0;
    }

    const jsFiles = glob.sync('./assets/js/*.js'); // Pattern for JS files
    const cssFiles = glob.sync('./assets/css/*.css'); // Pattern for CSS files

    // Process JavaScript files
    jsFiles.forEach(file => {
        if (isNotEmptyFile(file) && !file.includes('.min.js')) { // Exclude empty and '.min.js' files
            const entryName = path.basename(file, '.js'); // Remove '.js' extension
            entries[entryName] = path.resolve(__dirname, file);
        }
    });

    // Process CSS files
    cssFiles.forEach(file => {
        if (isNotEmptyFile(file) && !file.includes('.min.css')) { // Exclude empty and '.min.css' files
            const entryName = path.basename(file, '.css'); // Remove '.css' extension
            entries[entryName] = path.resolve(__dirname, file);
        }
    });

    return entries;
}

module.exports = {
    entry: getEntryPoints(),
    output: {
        path: path.resolve(__dirname, 'dist'), // Output directory
        filename: '[name].js' // Output filename
    },
    mode: 'production', // Set mode to production
    module: {
        rules: [
            {
                test: /\.js$/,
                exclude: /node_modules/,
                use: {
                    loader: 'babel-loader', // Transpile modern JS
                    options: {
                        presets: ['@babel/preset-env', '@babel/preset-react'],
                    },
                },
            },
            {
                test: /\.css$/,
                exclude: /node_modules/,
                use: [
                    MiniCssExtractPlugin.loader, // Extract CSS to files
                    'css-loader' // Handle CSS imports and extract to files
                ],
            },
        ],
    },
    plugins: [
        new MiniCssExtractPlugin({
            filename: '[name].css', // Extracted CSS filename
        })
    ],
    optimization: {
        minimize: true,
        minimizer: [
            new TerserPlugin(), // Minify JS using TerserPlugin
            new CssMinimizerPlugin(), // Minify CSS using CssMinimizerPlugin
        ]
    },
    resolve: {
        extensions: ['.js', '.jsx', '.css'], // Resolve these extensions
    },
};
