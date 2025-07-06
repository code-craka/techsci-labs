# 📧 TechSci Labs Email Testing Platform

> **Professional email testing service with temporary email addresses, API access, and real-time monitoring.**

[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)
[![Node.js Version](https://img.shields.io/badge/node-%3E%3D18.0.0-brightgreen)](https://nodejs.org/)
[![PHP Version](https://img.shields.io/badge/php-%3E%3D8.2-blue)](https://php.net/)
[![Docker](https://img.shields.io/badge/docker-ready-blue)](https://docker.com/)

## 🚀 Features

- **📨 Temporary Email Accounts** - Create disposable email addresses for testing
- **🔌 REST API** - Full API access for automation and integration
- **⚡ Real-time Updates** - Server-Sent Events via Mercure for live email monitoring
- **📬 Multiple Protocols** - IMAP, POP3, SMTP support
- **🌐 Webmail Interface** - Built-in web-based email client
- **➕ Plus Sign Aliasing** - Automatic email aliasing with `+` syntax
- **🕳️ Catch-All Addresses** - Capture emails to undefined addresses
- **🔒 Secure & Fast** - Modern security practices with high performance

## 🏗️ Architecture

```mermaid
graph TB
    A[Client Browser] --> B[Caddy Reverse Proxy]
    B --> C[Nuxt.js Frontend]
    B --> D[API Platform Backend]
    B --> E[Webmail Interface]
    B --> F[Mercure Hub]
    
    C --> D
    C --> F
    D --> G[MongoDB]
    D --> H[Redis]
    D --> F
    
    I[Email Server - Haraka] --> G
    I --> D
    
    J[External SMTP] --> I
```

### Tech Stack

- **Frontend**: Nuxt.js 3.x (Vue.js, TypeScript, Tailwind CSS)
- **Backend**: Symfony 7.1 + API Platform 3.2
- **Email Server**: Haraka (Node.js SMTP server)
- **Database**: MongoDB 7.0
- **Cache**: Redis 7.2
- **Real-time**: Mercure (Server-Sent Events)
- **Web Server**: Caddy 2.7
- **Package Manager**: pnpm
- **Containerization**: Docker & Docker Compose

## 📋 Prerequisites

- **Docker** & **Docker Compose** (v2.0+)
- **Node.js** (v18.0+)
- **PHP** (v8.2+)
- **pnpm** (v8.0+)
- **Git**

## ⚡ Quick Start

### 1. Clone the Repository

```bash
git clone https://github.com/techsci-labs/email-testing-platform.git
cd email-testing-platform
```

### 2. Environment Setup

```bash
# Copy environment configuration
cp .env.example .env

# Edit configuration (optional for development)
nano .env
```

### 3. Install Dependencies

```bash
# Install all workspace dependencies
pnpm install

# Or install per workspace
pnpm install --filter frontend
pnpm install --filter email-server
composer install --working-dir=backend
```

### 4. Start Development Environment

```bash
# Start all services
docker-compose up -d

# View logs
docker-compose logs -f

# Stop services
docker-compose down
```

### 5. Access the Platform

- **Frontend**: <http://localhost:3000>
- **API Documentation**: <http://localhost:8000/api/docs>
- **Webmail**: <http://localhost:8080>
- **Mercure Hub**: <http://localhost:3001/.well-known/mercure>
- **Portainer (Docker UI)**: <http://localhost:9000>

## 🛠️ Development

### Project Structure

```
techsci-email-testing/
├── frontend/                 # Nuxt.js 3.x Frontend
│   ├── components/          # Vue components
│   ├── pages/              # Route pages
│   ├── composables/        # Vue composables
│   ├── assets/             # Static assets
│   └── nuxt.config.ts      # Nuxt configuration
├── backend/                 # Symfony/API-Platform Backend
│   ├── src/                # PHP source code
│   ├── config/             # Symfony configuration
│   ├── migrations/         # Database migrations
│   └── composer.json       # PHP dependencies
├── email-server/           # Haraka Email Server
│   ├── config/             # Haraka configuration
│   ├── plugins/            # Custom plugins
│   └── package.json        # Node.js dependencies
├── infrastructure/         # Infrastructure configuration
│   ├── caddy/              # Caddy web server config
│   ├── mongodb/            # MongoDB configuration
│   └── redis/              # Redis configuration
├── docs/                   # Documentation
├── docker-compose.yml      # Development environment
└── package.json           # Workspace configuration
```

### Development Commands

```bash
# Frontend Development
pnpm dev:frontend           # Start Nuxt.js dev server
pnpm build:frontend         # Build for production
pnpm test:frontend          # Run frontend tests

# Backend Development
pnpm dev:backend            # Start Symfony dev server
pnpm test:backend           # Run backend tests
composer install            # Install PHP dependencies

# Email Server Development
pnpm dev:email              # Start Haraka in development mode
pnpm logs:email             # View email server logs

# Full Stack
pnpm dev                    # Start all development servers
pnpm build                  # Build all projects
pnpm test                   # Run all tests
pnpm lint                   # Lint all code
```

### API Development

#### Create API Token

```bash
curl -X POST "http://localhost:8000/api/tokens" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Development Token",
    "description": "For local development"
  }'
```

#### Create Email Account

```bash
curl -X POST "http://localhost:8000/api/accounts" \
  -H "X-API-KEY: your_api_key_here" \
  -H "Content-Type: application/json" \
  -d '{
    "address": "test@techsci.dev",
    "password": "password123"
  }'
```

#### Real-time Email Monitoring

```javascript
// Connect to Mercure for real-time updates
const eventSource = new EventSource(
  'http://localhost:3001/.well-known/mercure?topic=/accounts/{accountId}',
  {
    headers: {
      Authorization: 'Bearer your_mercure_token_here'
    }
  }
)

eventSource.onmessage = (event) => {
  const data = JSON.parse(event.data)
  if (data.type === 'Message') {
    console.log('New email received:', data)
  }
}
```

## 🧪 Testing

### Running Tests

```bash
# All tests
pnpm test

# Frontend tests (Vitest)
pnpm test:frontend
pnpm test:frontend:ui        # Open test UI
pnpm test:frontend:coverage  # Generate coverage

# Backend tests (PHPUnit)
pnpm test:backend
composer test:coverage       # Generate coverage

# Email server tests
pnpm test:email
```

### Test Data

```bash
# Load test fixtures
docker-compose exec backend php bin/console doctrine:fixtures:load

# Reset test database
docker-compose exec backend php bin/console doctrine:database:drop --force --env=test
docker-compose exec backend php bin/console doctrine:database:create --env=test
```

## 📊 Monitoring & Logs

### View Logs

```bash
# All services
docker-compose logs -f

# Specific service
docker-compose logs -f frontend
docker-compose logs -f backend
docker-compose logs -f email-server

# MongoDB logs
docker-compose exec mongodb mongosh --eval "db.adminCommand('getLog', 'global')"
```

### Health Checks

```bash
# Check service health
curl http://localhost:8000/api/health
curl http://localhost:3000/api/health
curl http://localhost:3001/.well-known/mercure

# Check email server
telnet localhost 25
```

## 🚀 Deployment

### Production Environment

```bash
# Build for production
APP_ENV=production docker-compose -f docker-compose.yml -f docker-compose.prod.yml up -d

# Or use environment variables
export APP_ENV=production
export NODE_ENV=production
docker-compose up -d
```

### Environment Variables

Key production variables:

```bash
# Security
APP_SECRET=your-production-secret
JWT_SECRET=your-jwt-secret
MERCURE_JWT_SECRET=your-mercure-secret

# Database
MONGODB_URI=mongodb://user:pass@host:27017/db
REDIS_URL=redis://host:6379

# Domains
SITE_URL=https://yourdomain.com
API_BASE_URL=https://api.yourdomain.com
```

### SSL/TLS Setup

Update your Caddyfile for automatic HTTPS:

```caddyfile
yourdomain.com {
    reverse_proxy frontend:3000
}

api.yourdomain.com {
    reverse_proxy backend:8000
}
```

## 🔧 Configuration

### Email Server (Haraka)

Edit `email-server/config/smtp.ini`:

```ini
[smtp]
listen=0.0.0.0:25,0.0.0.0:587,0.0.0.0:2525

[tls]
enabled=true
requestCert=false
```

### Database (MongoDB)

Edit `infrastructure/mongodb/mongod.conf`:

```yaml
storage:
  dbPath: /data/db
  engine: wiredTiger

net:
  port: 27017
  bindIp: 0.0.0.0
```

## 🤝 Contributing

Please read [CONTRIBUTING.md](CONTRIBUTING.md) for details on our code of conduct and the process for submitting pull requests.

### Development Workflow

1. Fork the repository
2. Create a feature branch: `git checkout -b feature/amazing-feature`
3. Make your changes
4. Run tests: `pnpm test`
5. Commit: `git commit -m 'feat: add amazing feature'`
6. Push: `git push origin feature/amazing-feature`
7. Open a Pull Request

## 📝 API Documentation

- **Interactive API Docs**: <http://localhost:8000/api/docs>
- **OpenAPI Spec**: <http://localhost:8000/api/docs.json>
- **Postman Collection**: Available in `/docs/postman/`

### Quick API Examples

```bash
# List domains
curl -X GET "http://localhost:8000/api/domains" \
  -H "X-API-KEY: your_api_key"

# Create account
curl -X POST "http://localhost:8000/api/accounts" \
  -H "X-API-KEY: your_api_key" \
  -H "Content-Type: application/json" \
  -d '{"address": "user@domain.com", "password": "secret"}'

# Get messages
curl -X GET "http://localhost:8000/api/accounts/{id}/mailboxes/{id}/messages" \
  -H "X-API-KEY: your_api_key"
```

## ⚖️ License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 🙋‍♂️ Support

- **Documentation**: <https://docs.techsci.dev>
- **Issues**: <https://github.com/techsci-labs/email-testing-platform/issues>
- **Discussions**: <https://github.com/techsci-labs/email-testing-platform/discussions>
- **Email**: <support@techsci.dev>

## 🌟 Acknowledgments

- [API Platform](https://api-platform.com/) - API development framework
- [Nuxt.js](https://nuxt.com/) - Vue.js framework
- [Haraka](https://haraka.github.io/) - SMTP server
- [Mercure](https://mercure.rocks/) - Real-time communication
- [Caddy](https://caddyserver.com/) - Web server

---

Made with ❤️ by TechSci Labs.