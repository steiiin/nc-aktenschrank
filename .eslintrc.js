module.exports = {
	extends: [
		'@nextcloud',
	],
	rules: {
		'jsdoc/require-jsdoc': 'off',
		'vue/first-attribute-linebreak': 'off',
		'vue/max-attributes-per-line': 'off',
		'vue/multiline-html-element-content-newline': 'off',
		'vue/html-indent': 'off',
		'indent': 'off',
		'no-unused-vars': 'warn',
		'no-debugger': 'off',	/* TODO: remove */
		'no-console': 'off',	/* TODO: remove */
	},
}
