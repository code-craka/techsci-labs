# TechSci Labs Email Testing Platform - Email Server Dockerfile
# Haraka SMTP Server with Node.js

# Base Node.js image
FROM node:20-alpine as base

# Install system dependencies
RUN apk add --no-cache \
    dumb-init \
    openssl \
    ca-certificates

# Create app user
RUN addgroup -g 1001 -S haraka && \
    adduser -S haraka -u 1001 -G haraka

# Set working directory
WORKDIR /app

# Development stage
FROM base as development

# Copy package files
COPY package.json package-lock.json* ./

# Install dependencies
RUN npm install

# Copy application code
COPY . .

# Create required directories
RUN mkdir -p logs queue temp && \
    chown -R haraka:haraka logs queue temp

# Switch to haraka user
USER haraka

# Expose ports
EXPOSE 25 587 2525 8025

# Set environment
ENV NODE_ENV=development

# Health check
HEALTHCHECK --interval=30s --timeout=10s --start-period=5s --retries=3 \
    CMD nc -z localhost 25 || exit 1

# Start Haraka
CMD ["dumb-init", "node", "node_modules/.bin/haraka", "-c", "/app"]

# Production stage
FROM base as production

# Copy package files
COPY package.json package-lock.json* ./

# Install production dependencies only
RUN npm install --omit=dev && npm cache clean --force

# Copy application code
COPY . .

# Create required directories with proper permissions
RUN mkdir -p logs queue temp && \
    chown -R haraka:haraka . && \
    chmod -R 755 .

# Switch to haraka user
USER haraka

# Expose ports
EXPOSE 25 587 2525 8025

# Set environment
ENV NODE_ENV=production

# Health check
HEALTHCHECK --interval=30s --timeout=10s --start-period=5s --retries=3 \
    CMD nc -z localhost 25 || exit 1

# Start Haraka with dumb-init for proper signal handling
CMD ["dumb-init", "node", "node_modules/.bin/haraka", "-c", "/app"]