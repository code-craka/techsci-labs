# 🏗️ TechSci Email Testing Platform - File Structure

## 📁 Complete Directory Structure

```
techsci-email-testing/
├── backend/                           # Symfony 7.1 + API Platform 3.2
│   ├── Entity/
│   │   ├── Domain.php                # ✅ Created - Domain management entity  
│   │   ├── Account.php               # ✅ Created - Email account entity
│   │   ├── Mailbox.php               # ✅ Created - Email folder entity
│   │   ├── Message.php               # ✅ Created - Email message entity
│   │   ├── EmailAddress.php          # ✅ Created - Email address embeddable
│   │   ├── Attachment.php            # 📝 Next - File attachment entity
│   │   └── Token.php                 # 📝 Next - API token entity
│   ├── Repository/
│   │   ├── DomainRepository.php      # 📝 Next - Domain queries
│   │   ├── AccountRepository.php     # 📝 Next - Account queries  
│   │   ├── MailboxRepository.php     # 📝 Next - Mailbox queries
│   │   ├── MessageRepository.php     # 📝 Next - Message queries
│   │   └── TokenRepository.php       # 📝 Next - Token queries
│   ├── Controller/
│   │   ├── Api/                      # API Platform controllers
│   │   │   ├── DomainController.php
│   │   │   ├── AccountController.php
│   │   │   └── AuthController.php
│   │   └── Admin/                    # Admin panel controllers
│   ├── Service/
│   │   ├── EmailProcessor.php        # 📝 Next - Email processing service
│   │   ├── AuthService.php           # 📝 Next - Authentication service
│   │   └── MercurePublisher.php      # 📝 Next - Real-time notifications
│   ├── EventListener/
│   │   ├── MessageListener.php       # 📝 Next - Message events
│   │   └── AuthListener.php          # 📝 Next - Auth events
│   ├── Security/
│   │   ├── ApiKeyAuthenticator.php   # 📝 Next - API key auth
│   │   └── JwtAuthenticator.php      # 📝 Next - JWT auth
│   └── DataFixtures/
│       └── AppFixtures.php           # 📝 Next - Test data
│   ├── config/
│   │   ├── packages/
│   │   │   ├── api_platform.yaml    # 📝 Next - API Platform config
│   │   │   ├── doctrine_mongodb.yaml # 📝 Next - MongoDB config
│   │   │   ├── mercure.yaml         # 📝 Next - Mercure config
│   │   │   ├── security.yaml        # 📝 Next - Security config
│   │   │   └── jwt.yaml             # 📝 Next - JWT config
│   │   └── routes/
│   │       ├── api_platform.yaml    # 📝 Next - API routes
│   │       └── admin.yaml           # 📝 Next - Admin routes
│   ├── composer.json                # 📝 Next - PHP dependencies
│   ├── .env.example                 # 📝 Next - Environment template
│   └── migrations/                  # 📝 Next - MongoDB migrations
│
├── frontend/                        # Nuxt.js 3.x Application
│   ├── components/
│   │   ├── Email/
│   │   │   ├── EmailList.vue        # 📝 Next - Email listing component
│   │   │   ├── EmailViewer.vue      # 📝 Next - Email viewer component
│   │   │   └── EmailComposer.vue    # 📝 Next - Email composer component
│   │   ├── Account/
│   │   │   ├── AccountForm.vue      # 📝 Next - Account management
│   │   │   └── AccountList.vue      # 📝 Next - Account listing
│   │   ├── Domain/
│   │   │   ├── DomainForm.vue       # 📝 Next - Domain management
│   │   │   └── DomainList.vue       # 📝 Next - Domain listing
│   │   ├── UI/
│   │   │   ├── Header.vue           # 📝 Next - App header
│   │   │   ├── Sidebar.vue          # 📝 Next - Navigation sidebar
│   │   │   └── Layout.vue           # 📝 Next - Main layout
│   │   └── Common/
│   │       ├── LoadingSpinner.vue   # 📝 Next - Loading states
│   │       └── ErrorMessage.vue     # 📝 Next - Error handling
│   ├── pages/
│   │   ├── index.vue                # 📝 Next - Dashboard home
│   │   ├── accounts/
│   │   │   ├── index.vue            # 📝 Next - Accounts page
│   │   │   └── [id].vue             # 📝 Next - Account details
│   │   ├── domains/
│   │   │   ├── index.vue            # 📝 Next - Domains page
│   │   │   └── [id].vue             # 📝 Next - Domain details
│   │   ├── messages/
│   │   │   ├── index.vue            # 📝 Next - Messages page
│   │   │   └── [id].vue             # 📝 Next - Message details
│   │   └── auth/
│   │       ├── login.vue            # 📝 Next - Login page
│   │       └── register.vue         # 📝 Next - Registration page
│   ├── plugins/
│   │   ├── api.client.ts            # 📝 Next - API client setup
│   │   ├── auth.client.ts           # 📝 Next - Auth plugin
│   │   └── sse.client.ts            # 📝 Next - Server-Sent Events
│   ├── composables/
│   │   ├── useApi.ts                # 📝 Next - API composable
│   │   ├── useAuth.ts               # 📝 Next - Auth composable
│   │   ├── useSSE.ts                # 📝 Next - SSE composable
│   │   └── useToast.ts              # 📝 Next - Toast notifications
│   ├── types/
│   │   ├── api.ts                   # 📝 Next - API type definitions
│   │   ├── auth.ts                  # 📝 Next - Auth types
│   │   └── email.ts                 # 📝 Next - Email types
│   ├── package.json                 # 📝 Next - Node.js dependencies
│   ├── nuxt.config.ts               # ✅ Created - Nuxt configuration
│   └── tailwind.config.js           # 📝 Next - Tailwind CSS config
│
├── email-server/                    # Haraka SMTP Server
│   ├── plugins/
│   │   ├── mongodb-storage.js       # 📝 Next - MongoDB email storage
│   │   ├── account-resolver.js      # 📝 Next - Email account resolution
│   │   ├── plus-aliasing.js         # 📝 Next - Plus sign aliasing
│   │   ├── catch-all.js             # 📝 Next - Catch-all functionality
│   │   └── mercure-notifier.js      # 📝 Next - Real-time notifications
│   ├── config/
│   │   ├── plugins                  # 📝 Next - Plugin configuration
│   │   ├── smtp.ini                 # 📝 Next - SMTP settings
│   │   └── log.ini                  # 📝 Next - Logging configuration
│   ├── package.json                 # ✅ Created - Node.js dependencies
│   └── server.js                    # 📝 Next - Haraka server entry
│
├── infrastructure/                  # Docker & Infrastructure
│   ├── caddy/
│   │   └── Caddyfile                # ✅ Created - Reverse proxy config
│   ├── mongodb/
│   │   ├── mongod.conf              # ✅ Created - MongoDB config
│   │   └── init-scripts/            # 📝 Next - DB initialization
│   ├── redis/
│   │   └── redis.conf               # ✅ Created - Redis config
│   └── mercure/
│       └── Caddyfile.mercure        # 📝 Next - Mercure hub config
│
├── docs/                            # Documentation
│   ├── api/                         # 📝 Next - API documentation
│   ├── setup/                       # 📝 Next - Setup guides
│   └── examples/                    # 📝 Next - Code examples
│
├── tests/                           # Test Suites
│   ├── backend/                     # PHPUnit tests
│   │   ├── Unit/                    # 📝 Next - Unit tests
│   │   └── Integration/             # 📝 Next - Integration tests
│   ├── frontend/                    # Vitest tests
│   │   ├── components/              # 📝 Next - Component tests
│   │   └── composables/             # 📝 Next - Composable tests
│   └── email-server/                # Mocha tests
│       └── plugins/                 # 📝 Next - Plugin tests
│
├── docker-compose.yml               # ✅ Created - Multi-service environment
├── .env.example                     # ✅ Created - Environment template
├── package.json                     # ✅ Created - Root workspace config
├── pnpm-workspace.yaml              # 📝 Next - pnpm workspace config
├── README.md                        # ✅ Created - Project documentation
├── CONTRIBUTING.md                  # ✅ Created - Development guidelines
├── Claude.md                        # ✅ Created - AI development guide
└── .gitignore                       # 📝 Next - Git ignore rules
```

## 📝 File Status Legend

- ✅ **Created** - File structure established
- 🔄 **In Progress** - Currently being developed
- 📝 **Next** - Ready for implementation

## 🎯 Phase 3 Implementation Order

### 1. Backend Core Entities (Current Priority)

```
backend/src/Entity/
├── Domain.php      ✅ Complete
├── Account.php     ✅ Complete  
├── Mailbox.php     ✅ Complete
├── Message.php     🔄 Completing now
├── Attachment.php  📝 Next
└── Token.php       📝 Next
```

### 2. Repository Classes

```
backend/src/Repository/
├── DomainRepository.php
├── AccountRepository.php
├── MailboxRepository.php
├── MessageRepository.php
└── TokenRepository.php
```

### 3. Core Services

```
backend/src/Service/
├── EmailProcessor.php
├── AuthService.php
└── MercurePublisher.php
```

### 4. Configuration Files

```
backend/config/packages/
├── api_platform.yaml
├── doctrine_mongodb.yaml
├── mercure.yaml
├── security.yaml
└── jwt.yaml
```

### 5. Email Server Setup

```
email-server/
├── plugins/         # Haraka plugins
├── config/          # Server configuration
└── server.js        # Entry point
```

This structure provides a clear roadmap for Phase 3 implementation with proper file locations and development priorities.