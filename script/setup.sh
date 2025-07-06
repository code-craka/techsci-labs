#!/bin/bash

# TechSci Labs Email Testing Platform - Complete Setup Script
# Run this script to create the entire monorepo structure

echo "🚀 Creating TechSci Labs Email Testing Platform Structure..."

# main project directory
cd all-coding-project/techsci-labs
# Initialize git repository
git init
echo "node_modules/" > .gitignore
echo ".env" >> .gitignore
echo "*.log" >> .gitignore

# Create root structure
mkdir -p docs/{api,development,infrastructure,user-guide}
mkdir -p infrastructure/{docker,ansible/{playbooks,inventory,roles},caddy,mongodb,systemd,scripts}
mkdir -p mercure
mkdir -p shared/{types,validation,constants}

# Root configuration files
touch README.md LICENSE CHANGELOG.md CONTRIBUTING.md Claude.md
touch docker-compose.yml .env.example

# Documentation structure
touch docs/api/{authentication.md,endpoints.md,mercure-sse.md}
touch docs/development/{getting-started.md,architecture.md,testing.md,deployment.md}
touch docs/infrastructure/{rockylinux-setup.md,caddy-configuration.md,mongodb-cluster.md,haraka-smtp.md}
touch docs/user-guide/{quick-start.md,features.md,troubleshooting.md}

# Infrastructure files
touch infrastructure/docker/{Dockerfile.frontend,Dockerfile.backend,Dockerfile.haraka,docker-compose.prod.yml}
touch infrastructure/ansible/playbooks/{rockylinux-setup.yml,caddy-setup.yml,mongodb-setup.yml,haraka-setup.yml}
touch infrastructure/ansible/inventory/{development,staging,production}
mkdir -p infrastructure/ansible/roles/{common,caddy,mongodb,nodejs}
touch infrastructure/caddy/{Caddyfile,Caddyfile.dev}
mkdir -p infrastructure/caddy/certs
touch infrastructure/mongodb/{mongod.conf,replica-set.js,indexes.js}
touch infrastructure/systemd/{haraka.service,mercure.service,backend.service}
touch infrastructure/scripts/{deploy.sh,backup.sh,restore.sh,health-check.sh}

# Mercure configuration
touch mercure/{mercure.yaml,Caddyfile.mercure,docker-compose.mercure.yml}

# Shared utilities
touch shared/types/{email.ts,account.ts,api.ts}
touch shared/validation/{schemas.ts,rules.ts}
touch shared/constants/{errors.ts,config.ts}

echo "✅ Root structure created"

# =====================================
# FRONTEND (Nuxt.js) SETUP
# =====================================

echo "🎨 Setting up Frontend (Nuxt.js)..."

mkdir frontend
cd frontend

# Create Nuxt.js project structure
mkdir -p assets/{css,images,icons}
mkdir -p components/{ui,layout,auth,dashboard,email,account,domain,common}
mkdir -p composables
mkdir -p layouts
mkdir -p middleware
mkdir -p pages/{dashboard/{accounts,domains,emails,api-keys,settings},docs}
mkdir -p plugins
mkdir -p public/{images,icons}
mkdir -p server/api/{proxy}
mkdir -p stores
mkdir -p types
mkdir -p utils
mkdir -p tests/{unit/{components,composables,utils},e2e}

# Frontend configuration files
touch package.json nuxt.config.ts tsconfig.json tailwind.config.ts
touch vitest.config.ts playwright.config.ts .env.example .gitignore
touch app.vue nuxt.d.ts

# Assets
touch assets/css/main.css

# Components structure
touch components/ui/{Button.vue,Input.vue,Card.vue,Dialog.vue,Badge.vue,Avatar.vue,index.ts}
touch components/layout/{Header.vue,Sidebar.vue,Footer.vue,Navigation.vue}
touch components/auth/{LoginForm.vue,RegisterForm.vue,ForgotPasswordForm.vue}
touch components/dashboard/{DashboardStats.vue,RecentEmails.vue,QuickActions.vue}
touch components/email/{EmailList.vue,EmailListItem.vue,EmailViewer.vue,EmailHeader.vue,EmailContent.vue,EmailAttachments.vue,EmailSearch.vue}
touch components/account/{AccountList.vue,AccountCard.vue,AccountForm.vue,AccountSettings.vue}
touch components/domain/{DomainList.vue,DomainCard.vue,DomainForm.vue,DnsSetup.vue}
touch components/common/{LoadingSpinner.vue,ErrorBoundary.vue,ConfirmDialog.vue,CopyButton.vue}

# Composables
touch composables/{useApi.ts,useAuth.ts,useEmail.ts,useAccount.ts,useDomain.ts,useMercure.ts,useToast.ts,useLocalStorage.ts}

# Layouts
touch layouts/{default.vue,auth.vue,dashboard.vue,landing.vue}

# Middleware
touch middleware/{auth.ts,guest.ts,admin.ts}

# Pages
touch pages/{index.vue,login.vue,register.vue,pricing.vue}
touch pages/dashboard/{index.vue}
touch pages/dashboard/accounts/{index.vue,[id].vue,new.vue}
touch pages/dashboard/domains/{index.vue,[id].vue,new.vue}
touch pages/dashboard/emails/{index.vue,[id].vue}
touch pages/dashboard/api-keys/{index.vue,new.vue}
touch pages/dashboard/settings/{index.vue,profile.vue,billing.vue,team.vue}
touch pages/docs/{index.vue,api.vue,guides.vue,examples.vue}

# Plugins
touch plugins/{api.client.ts,mercure.client.ts,toast.client.ts}

# Public assets
touch public/{favicon.ico,logo.svg}

# Server API
touch server/api/health.get.ts
touch server/api/proxy/[...].ts

# Stores
touch stores/{auth.ts,email.ts,account.ts,domain.ts,ui.ts}

# Types
touch types/{auth.ts,email.ts,account.ts,domain.ts,api.ts,global.d.ts}

# Utils
touch utils/{api.ts,validation.ts,formatting.ts,constants.ts}

# Tests
touch tests/{setup.ts}
touch tests/unit/components/.gitkeep
touch tests/unit/composables/.gitkeep
touch tests/unit/utils/.gitkeep
touch tests/e2e/{auth.spec.ts,dashboard.spec.ts,email-management.spec.ts}

cd ..
echo "✅ Frontend structure created"

# =====================================
# BACKEND (API-Platform/Symfony) SETUP
# =====================================

echo "🔧 Setting up Backend (API-Platform/Symfony)..."

mkdir backend
cd backend

# Backend structure
mkdir -p bin
mkdir -p config/packages
mkdir -p src/{Controller,Document,Repository,Service,EventListener,Security,Validator,Serializer}
mkdir -p templates
mkdir -p var/{cache,log}
mkdir -p vendor
mkdir -p tests/{Api,Unit/{Service,Validator}}

# Backend configuration files
touch composer.json composer.lock symfony.lock .env .env.test .gitignore

# Bin
touch bin/console

# Config
touch config/{bundles.php,services.yaml,routes.yaml}
touch config/packages/{api_platform.yaml,doctrine_mongodb.yaml,mercure.yaml,security.yaml,validator.yaml}

# Source files
touch src/Controller/{AuthController.php,HealthController.php,WebhookController.php}
touch src/Document/{Domain.php,EmailAccount.php,Mailbox.php,Message.php,Attachment.php,Token.php}
touch src/Repository/{DomainRepository.php,EmailAccountRepository.php,MailboxRepository.php,MessageRepository.php}
touch src/Service/{EmailProcessingService.php,MercurePublisher.php,AuthenticationService.php,ValidationService.php}
touch src/EventListener/{EmailReceivedListener.php,AuthenticationListener.php,ExceptionListener.php}
touch src/Security/{ApiKeyAuthenticator.php,UserProvider.php}
touch src/Validator/{EmailAddressValidator.php,DomainValidator.php}
touch src/Serializer/{EmailNormalizer.php,AccountNormalizer.php}
touch src/Kernel.php

# Tests
touch tests/{bootstrap.php}
touch tests/Api/{DomainTest.php,EmailAccountTest.php,MessageTest.php}
touch tests/Unit/Service/.gitkeep
touch tests/Unit/Validator/.gitkeep

cd ..
echo "✅ Backend structure created"

# =====================================
# EMAIL SERVER (Haraka) SETUP
# =====================================

echo "📧 Setting up Email Server (Haraka)..."

mkdir email-server
cd email-server

# Email server structure
mkdir -p config
mkdir -p plugins
mkdir -p lib
mkdir -p logs
mkdir -p tests/{unit,integration}

# Email server files
touch package.json package-lock.json .env

# Config
touch config/{smtp.ini,plugins,host_list,me,smtp.json}

# Plugins
touch plugins/{email-storage.js,plus-aliasing.js,catch-all.js,mongodb-logger.js,mercure-publisher.js}

# Lib
touch lib/{mongodb-client.js,email-parser.js,utils.js}

# Tests
touch tests/unit/.gitkeep
touch tests/integration/.gitkeep

cd ..
echo "✅ Email server structure created"

# =====================================
# ROOT PACKAGE.JSON SETUP
# =====================================

echo "📦 Creating root package.json with workspaces..."

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
    "infrastructure:down": "docker-compose down"
  },
  "devDependencies": {
    "@commitlint/cli": "^18.4.4",
    "@commitlint/config-conventional": "^18.4.4",
    "husky": "^8.0.3",
    "lint-staged": "^15.2.0",
    "standard-version": "^9.5.0"
  }
}
EOF

# =====================================
# INITIALIZE PNPM WORKSPACE
# =====================================

echo "📦 Initializing pnpm workspace..."

# Create pnpm-workspace.yaml
cat > pnpm-workspace.yaml << 'EOF'
packages:
  - 'frontend'
  - 'email-server'
  - 'shared'
EOF

# Initialize each workspace
echo "📦 Setting up individual workspaces..."

# Frontend package.json
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
    "@nuxt/eslint-config": "^0.2.0",
    "@nuxt/test-utils": "^3.9.0",
    "@playwright/test": "^1.40.0",
    "@vue/test-utils": "^2.4.0",
    "eslint": "^8.56.0",
    "happy-dom": "^12.10.3",
    "nuxt": "^3.8.0",
    "typescript": "^5.3.0",
    "vitest": "^1.0.0"
  },
  "dependencies": {
    "@nuxtjs/tailwindcss": "^6.8.4",
    "@pinia/nuxt": "^0.5.1",
    "@vueuse/core": "^10.7.0",
    "@vueuse/nuxt": "^10.7.0",
    "pinia": "^2.1.7",
    "vue": "^3.3.0",
    "zod": "^3.22.4"
  }
}
EOF

# Email server package.json
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
    "haraka": "^3.0.0",
    "mongodb": "^6.3.0",
    "axios": "^1.6.0"
  },
  "devDependencies": {
    "@types/node": "^20.10.0",
    "eslint": "^8.56.0",
    "jest": "^29.7.0",
    "typescript": "^5.3.0"
  }
}
EOF

# Shared package.json
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
    "@types/node": "^20.10.0",
    "eslint": "^8.56.0",
    "jest": "^29.7.0",
    "typescript": "^5.3.0"
  },
  "dependencies": {
    "zod": "^3.22.4"
  }
}
EOF

# Create shared index.ts
cat > shared/index.ts << 'EOF'
// Export all shared types and utilities
export * from './types/email'
export * from './types/account'
export * from './types/api'
export * from './validation/schemas'
export * from './constants/errors'
export * from './constants/config'
EOF

echo "✅ All workspaces configured"

# =====================================
# FINAL SETUP COMMANDS
# =====================================

echo "🎯 Final setup commands..."

# Create basic .gitignore for each workspace
cat > frontend/.gitignore << 'EOF'
# Nuxt dev/build outputs
.output
.data
.nuxt
.nitro
.cache
dist

# Node dependencies
node_modules

# Logs
*.log

# Misc
.DS_Store
.fleet
.idea

# Local env files
.env
.env.*
!.env.example
EOF

cat > email-server/.gitignore << 'EOF'
# Node dependencies
node_modules

# Logs
*.log
logs/

# Misc
.DS_Store

# Local env files
.env
.env.*
!.env.example
EOF

cat > backend/.gitignore << 'EOF'
# Symfony specific
/var/
/vendor/
.env.local
.env.local.php
.env.*.local

# Logs
*.log

# Cache
/var/cache/
/var/log/

# PHP specific
composer.phar
EOF

echo "🚀 Project structure created successfully!"
echo ""
echo "Next steps:"
echo "1. cd techsci-labs"
echo "2. pnpm install (installs all workspace dependencies)"
echo "3. cd backend && composer install (install PHP dependencies)"
echo "4. Configure your .env files"
echo "5. pnpm dev (start all services in development mode)"
echo ""
echo "Individual commands:"
echo "- pnpm frontend:dev    # Start Nuxt.js frontend"
echo "- pnpm email-server:dev # Start Haraka email server"
echo "- pnpm backend:dev     # Start Symfony API server"
echo ""
echo "✨ Happy coding!"