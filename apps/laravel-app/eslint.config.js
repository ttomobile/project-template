import js from '@eslint/js';
import importPlugin from 'eslint-plugin-import';
import prettierPlugin from 'eslint-plugin-prettier';

export default [
  {
    ignores: ['public/**', 'vendor/**', 'storage/**', 'bootstrap/cache/**', 'node_modules/**'],
  },
  js.configs.recommended,
  {
    files: ['resources/js/**/*.{js,jsx,ts,tsx}'],
    plugins: {
      import: importPlugin,
      prettier: prettierPlugin,
    },
    languageOptions: {
      sourceType: 'module',
      ecmaVersion: 'latest',
      globals: {
        window: 'readonly',
        document: 'readonly',
        console: 'readonly',
      },
    },
    rules: {
      ...importPlugin.configs.recommended.rules,
      'prettier/prettier': 'error',
    },
  },
];
