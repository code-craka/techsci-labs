#!/bin/bash

echo "🔧 Fixing TechSci Labs dependencies and security issues..."

# Fix frontend package.json with updated and secure versions
echo "📦 Updating frontend dependencies..."

cat > frontend/package.json << 'EOF'
{
  "name": "@techsci-labs/frontend",
  "private": true,
  "version": "1.0.0",
  "scripts": {
    "build": "nuxt build",
    "dev": "nuxt dev",
    "generate": "nuxt generate",
    "preview": "nuxt preview",
    "postinstall": "nuxt prepare",
    "start": "node .output/server/index.mjs",
    "lint": "eslint .",
    "lint:fix": "eslint . --fix",
    "test": "vitest",
    "test:e2e": "playwright test",
    "type-check": "nuxt typecheck"
  },
  "devDependencies": {
    "@nuxt/devtools": "latest",
    "@nuxt/eslint-config": "^0.7.3",
    "@nuxt/test-utils": "^3.19.2",
    "@playwright/test": "^1.49.1",
    "@vue/test-utils": "^2.4.6",
    "eslint": "^9.17.0",
    "happy-dom": "^15.11.7",
    "nuxt": "^3.14.1592",
    "typescript": "^5.8.3",
    "vitest": "^2.1.8"
  },
  "dependencies": {
    "@nuxtjs/tailwindcss": "^6.12.1",
    "@pinia/nuxt": "^0.5.5",
    "@vueuse/core": "^11.3.0",
    "@vueuse/nuxt": "^11.3.0",
    "pinia": "^2.2.8",
    "vue": "^3.5.17",
    "zod": "^3.24.1"
  }
}
EOF

# Update root package.json with latest versions
echo "📦 Updating root package.json..."

cat > package.json << 'EOF'
{
  "name": "techsci-labs",
  "version": "1.0.0",
  "description": "Professional email testing platform built with Nuxt.js and API-Platform",
  "private": true,
  "license": "MIT",
  "author": {
    "name": "TechSci Labs",
    "email": "hello@techsci.dev",
    "url": "https://techsci.dev"
  },
  "engines": {
    "node": ">=18.17.0",
    "pnpm": ">=8.0.0"
  },
  "packageManager": "pnpm@8.15.1",
  "workspaces": [
    "frontend",
    "email-server",
    "shared"
  ],
  "scripts": {
    "dev": "pnpm --parallel run dev",
    "build": "pnpm --parallel run build",
    "start": "pnpm --parallel run start",
    "lint": "pnpm --parallel run lint",
    "test": "pnpm --parallel run test",
    "clean": "pnpm --parallel run clean",
    "frontend:dev": "pnpm --filter frontend dev",
    "frontend:build": "pnpm --filter frontend build",
    "email-server:dev": "pnpm --filter email-server dev",
    "backend:install": "cd backend && composer install",
    "backend:dev": "cd backend && symfony server:start",
    "infrastructure:up": "docker-compose up -d",
    "infrastructure:down": "docker-compose down",
    "security:audit": "pnpm audit --audit-level moderate",
    "security:fix": "pnpm audit --fix"
  },
  "devDependencies": {
    "@commitlint/cli": "^19.8.1",
    "@commitlint/config-conventional": "^19.8.1",
    "husky": "^9.1.7",
    "lint-staged": "^16.1.2",
    "standard-version": "^9.5.0"
  }
}
EOF

# Update email-server package.json with better versions
echo "📦 Updating email-server dependencies..."

cat > email-server/package.json << 'EOF'
{
  "name": "@techsci-labs/email-server",
  "private": true,
  "version": "1.0.0",
  "scripts": {
    "dev": "haraka -c .",
    "start": "haraka -c .",
    "test": "jest",
    "lint": "eslint .",
    "lint:fix": "eslint . --fix"
  },
  "dependencies": {
    "Haraka": "^3.1.1",
    "mongodb": "^6.10.0",
    "axios": "^1.7.9"
  },
  "devDependencies": {
    "@types/node": "^22.10.2",
    "eslint": "^9.17.0",
    "jest": "^29.7.0",
    "typescript": "^5.8.3"
  }
}
EOF

# Update shared package.json
echo "📦 Updating shared dependencies..."

cat > shared/package.json << 'EOF'
{
  "name": "@techsci-labs/shared",
  "private": true,
  "version": "1.0.0",
  "main": "index.ts",
  "scripts": {
    "build": "tsc",
    "dev": "tsc --watch",
    "lint": "eslint .",
    "lint:fix": "eslint . --fix",
    "test": "jest"
  },
  "devDependencies": {
    "@types/node": "^22.10.2",
    "eslint": "^9.17.0",
    "jest": "^29.7.0",
    "typescript": "^5.8.3"
  },
  "dependencies": {
    "zod": "^3.24.1"
  }
}
EOF

# Create pnpm overrides to fix security issues
echo "📦 Creating security overrides..."

cat > .npmrc << 'EOF'
# Security and dependency resolution
auto-install-peers=true
strict-peer-dependencies=false
prefer-frozen-lockfile=false
EOF

# Add resolutions to package.json to force secure versions
echo "📦 Adding dependency resolutions for security..."

# Create a temporary package.json with resolutions
cat > package.json << 'EOF'
{
  "name": "techsci-labs",
  "version": "1.0.0",
  "description": "Professional email testing platform built with Nuxt.js and API-Platform",
  "private": true,
  "license": "MIT",
  "author": {
    "name": "TechSci Labs",
    "email": "hello@techsci.dev",
    "url": "https://techsci.dev"
  },
  "engines": {
    "node": ">=18.17.0",
    "pnpm": ">=8.0.0"
  },
  "packageManager": "pnpm@8.15.1",
  "workspaces": [
    "frontend",
    "email-server",
    "shared"
  ],
  "scripts": {
    "dev": "pnpm --parallel run dev",
    "build": "pnpm --parallel run build",
    "start": "pnpm --parallel run start",
    "lint": "pnpm --parallel run lint",
    "test": "pnpm --parallel run test",
    "clean": "pnpm --parallel run clean",
    "frontend:dev": "pnpm --filter frontend dev",
    "frontend:build": "pnpm --filter frontend build",
    "email-server:dev": "pnpm --filter email-server dev",
    "backend:install": "cd backend && composer install",
    "backend:dev": "cd backend && symfony server:start",
    "infrastructure:up": "docker-compose up -d",
    "infrastructure:down": "docker-compose down",
    "security:audit": "pnpm audit --audit-level moderate",
    "security:fix": "pnpm audit --fix"
  },
  "devDependencies": {
    "@commitlint/cli": "^19.8.1",
    "@commitlint/config-conventional": "^19.8.1",
    "husky": "^9.1.7",
    "lint-staged": "^16.1.2",
    "standard-version": "^9.5.0"
  },
  "pnpm": {
    "overrides": {
      "happy-dom": ">=15.11.7",
      "esbuild": ">=0.25.5",
      "vitest": ">=2.1.8",
      "@nuxt/test-utils": ">=3.19.2"
    },
    "peerDependencyRules": {
      "ignoreMissing": [
        "vitest"
      ]
    }
  }
}
EOF

echo "✅ Dependencies updated!"
echo ""
echo "🔧 Next steps:"
echo "1. Clear node_modules and lock file:"
echo "   rm -rf node_modules pnpm-lock.yaml"
echo ""
echo "2. Reinstall with security fixes:"
echo "   pnpm install"
echo ""
echo "3. Check security status:"
echo "   pnpm audit"
echo ""
echo "4. If still issues, run:"
echo "   pnpm audit --fix"