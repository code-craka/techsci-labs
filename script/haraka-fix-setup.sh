#!/bin/bash

# Fix Haraka package issue
echo "🔧 Fixing Haraka package installation..."

# Update email-server package.json with correct Haraka package name and version
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

echo "✅ Fixed email-server package.json with correct Haraka package name"
echo ""
echo "Now run: pnpm install"