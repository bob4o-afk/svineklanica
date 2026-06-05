import js from '@eslint/js';
import globals from 'globals';
import tseslint from 'typescript-eslint';
import reactHooks from 'eslint-plugin-react-hooks';
import reactRefresh from 'eslint-plugin-react-refresh';

// Enforces the TS discipline from .claude/rules/frontend.md §4–§5:
//  - no console.* (use the logger module)
//  - no `as any` (cast at the narrowest real boundary instead)
export default tseslint.config(
  { ignores: ['dist', 'dev-dist', 'coverage', 'public/mockServiceWorker.js'] },
  {
    extends: [js.configs.recommended, ...tseslint.configs.recommended],
    files: ['**/*.{ts,tsx}'],
    languageOptions: {
      ecmaVersion: 2022,
      globals: globals.browser,
    },
    plugins: {
      'react-hooks': reactHooks,
      'react-refresh': reactRefresh,
    },
    rules: {
      ...reactHooks.configs.recommended.rules,
      'react-refresh/only-export-components': ['warn', { allowConstantExport: true }],
      'no-console': 'error',
      '@typescript-eslint/no-explicit-any': 'error',
      '@typescript-eslint/no-unused-vars': ['error', { argsIgnorePattern: '^_' }],
      // Security: ban dangerouslySetInnerHTML (XSS). Render text; if HTML is ever
      // unavoidable, sanitize with a DOMPurify allow-list. (.claude/rules/security)
      'no-restricted-syntax': [
        'error',
        {
          selector: "JSXAttribute[name.name='dangerouslySetInnerHTML']",
          message:
            'dangerouslySetInnerHTML is banned (XSS risk). Render text, or sanitize with DOMPurify.',
        },
      ],
    },
  },
);
