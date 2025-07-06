module.exports = {
  env: {
    node: true,
    es2022: true,
    mocha: true
  },
  extends: [
    'standard'
  ],
  parserOptions: {
    ecmaVersion: 'latest',
    sourceType: 'module'
  },
  rules: {
    // Allow console.log in server environment
    'no-console': 'off',
    
    // Allow process.exit in server scripts
    'no-process-exit': 'off',
    
    // Standard rules with some relaxations for Haraka development
    'space-before-function-paren': ['error', {
      'anonymous': 'always',
      'named': 'never',
      'asyncArrow': 'always'
    }],
    
    // Allow callback patterns common in Haraka
    'node/no-callback-literal': 'off',
    
    // Allow require() calls for dynamic imports in plugins
    'import/no-dynamic-require': 'off',
    
    // Allow function hoisting for plugin structure
    'no-use-before-define': ['error', { 'functions': false }],
    
    // Allow both single and double quotes
    'quotes': ['error', 'single', { 'allowTemplateLiterals': true }],
    
    // Allow trailing comma in multiline
    'comma-dangle': ['error', 'only-multiline'],
    
    // Standard indentation
    'indent': ['error', 2],
    
    // Standard semicolon rules
    'semi': ['error', 'never'],
    
    // Allow multiple empty lines for better code organization
    'no-multiple-empty-lines': ['error', { 'max': 3, 'maxEOF': 1 }],
    
    // Allow unused vars that start with underscore
    'no-unused-vars': ['error', { 'argsIgnorePattern': '^_' }]
  },
  overrides: [
    {
      files: ['test/**/*.js', '**/*.test.js', '**/*.spec.js'],
      env: {
        mocha: true
      },
      rules: {
        // Allow longer lines in tests
        'max-len': 'off',
        
        // Allow expressions in tests
        'no-unused-expressions': 'off'
      }
    },
    {
      files: ['config/**/*.js'],
      rules: {
        // Config files might have different patterns
        'no-undef': 'off'
      }
    },
    {
      files: ['plugins/**/*.js'],
      rules: {
        // Haraka plugins have specific patterns
        'no-unused-vars': ['error', { 
          'argsIgnorePattern': '^(next|connection|params|_)',
          'varsIgnorePattern': '^_'
        }]
      }
    }
  ],
  ignorePatterns: [
    'node_modules/',
    'logs/',
    'queue/',
    'tmp/',
    'coverage/',
    '*.min.js'
  ]
}