const path = require('path')
const webpackConfig = require('@nextcloud/webpack-vue-config')
const ESLintPlugin = require('eslint-webpack-plugin')
const StyleLintPlugin = require('stylelint-webpack-plugin')
const buildMode = process.env.NODE_ENV
const isDev = buildMode === 'development'
webpackConfig.devtool = isDev ? 'cheap-source-map' : 'source-map'

// const webpackRules = require('@nextcloud/webpack-vue-config/rules')
// webpackConfig.bail = false
webpackConfig.stats = {
	colors: true,
	modules: false,
}

// Include mdi icons as raw svg strings
// webpackRules.RULE_SVG = {
// resourceQuery: /raw/,
// type: 'asset/source',
// }
// webpackConfig.module.rules = Object.values(webpackRules)

const appId = 'pdfsignature'
webpackConfig.entry = {
	main: { import: path.join(__dirname, 'src', 'filesplugin.js'), filename: appId + '-filesplugin.js' },
	adminSettings: { import: path.join(__dirname, 'src', 'adminSettings.js'), filename: appId + '-adminSettings.js' },

}
webpackConfig.plugins.push(
	new ESLintPlugin({
		extensions: ['js', 'vue'],
		files: 'src',
		failOnError: !isDev,
	}),
)
webpackConfig.plugins.push(
	new StyleLintPlugin({
		files: 'src/**/*.{css,scss,vue}',
		failOnError: !isDev,
	}),
)
webpackConfig.module.rules.push({
	test: /\.svg$/i,
	type: 'asset/source',
})

module.exports = webpackConfig
