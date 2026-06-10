import eslint from '@eslint/js';
import tseslint from 'typescript-eslint';
import react from 'eslint-plugin-react';
import reactHooks from 'eslint-plugin-react-hooks';
import globals from 'globals';

export default tseslint.config(
	// Base JavaScript recommended rules
	eslint.configs.recommended,

	// TypeScript recommended rules
	...tseslint.configs.recommended,

	// React and TypeScript configuration
	{
		files: ['**/*.{ts,tsx,js,jsx}'],

		plugins: {
			react,
			'react-hooks': reactHooks,
		},

		languageOptions: {
			parser: tseslint.parser,
			parserOptions: {
				ecmaVersion: 'latest',
				sourceType: 'module',
				ecmaFeatures: {
					jsx: true,
				},
			},
			globals: {
				...globals.browser,
			},
		},

		settings: {
			react: {
				version: 'detect',
			},
		},

		rules: {
			// React recommended rules
			...react.configs.recommended.rules,

			// React Hooks rules
			'react-hooks/rules-of-hooks': 'error',
			'react-hooks/exhaustive-deps': 'warn',

			// TypeScript specific adjustments
			'@typescript-eslint/no-explicit-any': 'warn',
			'@typescript-eslint/explicit-module-boundary-types': 'off',
			'@typescript-eslint/no-unused-vars': ['warn', {
				argsIgnorePattern: '^_',
				varsIgnorePattern: '^_',
			}],

			// General rules
			'no-console': 'off',
			'prefer-const': 'warn',
			'no-var': 'error',
		},
	},

	// Ignore patterns
	{
		ignores: [
			'node_modules/**',
		],
	}
);
