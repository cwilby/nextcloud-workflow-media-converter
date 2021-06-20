module.exports = {
	globals: {
		appVersion: true,
	},
	parserOptions: {
		requireConfigFile: false,
	},
	extends: ['@nextcloud'],
	rules: {
		'import/no-extraneous-dependencies': [
			'error',
			{
				devDependencies: false,
				optionalDependencies: false,
				peerDependencies: false,
			},
		],
		'vue/html-indent': ['error', 'tab'],
	},
}
