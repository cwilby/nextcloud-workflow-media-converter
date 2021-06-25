const path = require('path')
const webpackConfig = require('@nextcloud/webpack-vue-config')

webpackConfig.devtool = 'cheap-source-map'

webpackConfig.stats = {
	colors: true,
	modules: true,
}

webpackConfig.entry = {
	personalSettings: { import: path.join(__dirname, 'src', 'personalSettings.js'), filename: 'workflow_media_converter-personalSettings.js' },
	adminSettings: { import: path.join(__dirname, 'src', 'adminSettings.js'), filename: 'workflow_media_converter-adminSettings.js' },
	operator: { import: path.join(__dirname, 'src', 'operator.js'), filename: 'workflow_media_converter-operator.js' },
}

module.exports = webpackConfig
