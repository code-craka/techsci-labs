import vue from 'eslint-plugin-vue'
import typescript from '@typescript-eslint/eslint-plugin'
import typescriptParser from '@typescript-eslint/parser'

export default [
  // Global ignores
  {
    ignores: [
      'node_modules/**',
      '.nuxt/**',
      '.output/**', 
      'dist/**',
      'coverage/**',
      '*.min.js',
      'public/**',
      '.nitro/**'
    ]
  },
  
  // Vue files configuration
  {
    files: ['**/*.vue'],
    languageOptions: {
      parser: vue.processors.vue.parser,
      parserOptions: {
        parser: typescriptParser,
        ecmaVersion: 'latest',
        sourceType: 'module'
      }
    },
    plugins: {
      vue,
      '@typescript-eslint': typescript
    },
    processor: vue.processors.vue,
    rules: {
      ...vue.configs.essential.rules,
      ...vue.configs['strongly-recommended'].rules,
      
      // Vue specific customizations
      'vue/multi-word-component-names': 'off',
      'vue/no-v-html': 'warn',
      'vue/require-default-prop': 'off',
      'vue/require-explicit-emits': 'error',
      'vue/component-tags-order': ['error', {
        'order': ['script', 'template', 'style']
      }],
      'vue/html-indent': ['error', 2],
      'vue/script-indent': ['error', 2, { 'baseIndent': 0 }],
      'vue/max-attributes-per-line': ['error', {
        'singleline': 3,
        'multiline': 1
      }],
      
      // General rules for Vue files
      'no-console': 'warn',
      'no-debugger': 'error',
      'prefer-const': 'error',
      'no-var': 'error',
      'indent': ['error', 2],
      'quotes': ['error', 'single', { 'allowTemplateLiterals': true }],
      'semi': ['error', 'never'],
      'comma-dangle': ['error', 'never'],
      'object-curly-spacing': ['error', 'always'],
      'array-bracket-spacing': ['error', 'never']
    }
  },
  
  // TypeScript files configuration
  {
    files: ['**/*.ts', '**/*.tsx'],
    languageOptions: {
      parser: typescriptParser,
      parserOptions: {
        ecmaVersion: 'latest',
        sourceType: 'module'
      }
    },
    plugins: {
      '@typescript-eslint': typescript
    },
    rules: {
      ...typescript.configs.recommended.rules,
      
      // TypeScript specific rules
      '@typescript-eslint/no-unused-vars': ['error', { 
        'argsIgnorePattern': '^_',
        'varsIgnorePattern': '^_'
      }],
      '@typescript-eslint/no-explicit-any': 'warn',
      '@typescript-eslint/prefer-const': 'error',
      '@typescript-eslint/no-non-null-assertion': 'warn',
      
      // General rules
      'no-console': 'warn',
      'no-debugger': 'error',
      'prefer-const': 'error',
      'no-var': 'error',
      'indent': ['error', 2],
      'quotes': ['error', 'single', { 'allowTemplateLiterals': true }],
      'semi': ['error', 'never'],
      'comma-dangle': ['error', 'never'],
      'object-curly-spacing': ['error', 'always'],
      'array-bracket-spacing': ['error', 'never']
    }
  },
  
  // JavaScript files configuration
  {
    files: ['**/*.js', '**/*.jsx', '**/*.mjs', '**/*.cjs'],
    languageOptions: {
      ecmaVersion: 'latest',
      sourceType: 'module'
    },
    rules: {
      // General JavaScript rules
      'no-console': 'warn',
      'no-debugger': 'error',
      'prefer-const': 'error',
      'no-var': 'error',
      'indent': ['error', 2],
      'quotes': ['error', 'single', { 'allowTemplateLiterals': true }],
      'semi': ['error', 'never'],
      'comma-dangle': ['error', 'never'],
      'object-curly-spacing': ['error', 'always'],
      'array-bracket-spacing': ['error', 'never']
    }
  },
  
  // Special file overrides
  {
    files: ['**/*.d.ts'],
    rules: {
      '@typescript-eslint/no-explicit-any': 'off',
      '@typescript-eslint/ban-types': 'off'
    }
  },
  
  {
    files: ['**/*.config.{js,ts}', '**/nuxt.config.{js,ts}'],
    rules: {
      'no-console': 'off',
      '@typescript-eslint/no-explicit-any': 'off'
    }
  },
  
  {
    files: ['**/*.test.{js,ts,vue}', '**/*.spec.{js,ts,vue}'],
    rules: {
      'no-console': 'off',
      '@typescript-eslint/no-explicit-any': 'off',
      'vue/one-component-per-file': 'off'
    }
  },
  
  {
    files: ['pages/**/*.vue', 'layouts/**/*.vue'],
    rules: {
      'vue/multi-word-component-names': 'off'
    }
  }
]