#!/usr/bin/env node

/**
 * Generate JWT tokens for Mercure Hub
 * This script generates both publisher and subscriber tokens for Mercure SSE
 */

const crypto = require('crypto');
const { readFileSync } = require('fs');
const { join } = require('path');

// Generate a secret if not provided
const MERCURE_JWT_SECRET = process.env.MERCURE_JWT_SECRET || 'techsci-mercure-dev-secret-key-change-in-production';

/**
 * Simple JWT implementation for Mercure
 */
function createJWT(payload, secret) {
  const header = {
    typ: 'JWT',
    alg: 'HS256'
  };
  
  const encodedHeader = Buffer.from(JSON.stringify(header)).toString('base64url');
  const encodedPayload = Buffer.from(JSON.stringify(payload)).toString('base64url');
  
  const signature = crypto
    .createHmac('sha256', secret)
    .update(`${encodedHeader}.${encodedPayload}`)
    .digest('base64url');
  
  return `${encodedHeader}.${encodedPayload}.${signature}`;
}

/**
 * Generate publisher JWT (can publish to any topic)
 */
function generatePublisherJWT() {
  const payload = {
    mercure: {
      publish: ['*'] // Can publish to any topic
    },
    iat: Math.floor(Date.now() / 1000),
    exp: Math.floor(Date.now() / 1000) + (365 * 24 * 60 * 60) // 1 year expiry
  };
  
  return createJWT(payload, MERCURE_JWT_SECRET);
}

/**
 * Generate subscriber JWT (can subscribe to any topic)
 */
function generateSubscriberJWT() {
  const payload = {
    mercure: {
      subscribe: ['*'] // Can subscribe to any topic
    },
    iat: Math.floor(Date.now() / 1000),
    exp: Math.floor(Date.now() / 1000) + (365 * 24 * 60 * 60) // 1 year expiry
  };
  
  return createJWT(payload, MERCURE_JWT_SECRET);
}

/**
 * Generate user-specific subscriber JWT
 */
function generateUserSubscriberJWT(userId) {
  const payload = {
    mercure: {
      subscribe: [
        `https://techsci.dev/accounts/${userId}`,
        `https://techsci.dev/messages/${userId}`,
        `https://techsci.dev/notifications/${userId}`
      ]
    },
    sub: userId,
    iat: Math.floor(Date.now() / 1000),
    exp: Math.floor(Date.now() / 1000) + (24 * 60 * 60) // 24 hours
  };
  
  return createJWT(payload, MERCURE_JWT_SECRET);
}

// Main execution
if (require.main === module) {
  const command = process.argv[2];
  
  switch (command) {
    case 'publisher':
      console.log(generatePublisherJWT());
      break;
      
    case 'subscriber':
      console.log(generateSubscriberJWT());
      break;
      
    case 'user':
      const userId = process.argv[3];
      if (!userId) {
        console.error('Usage: node generate-mercure-jwt.js user <userId>');
        process.exit(1);
      }
      console.log(generateUserSubscriberJWT(userId));
      break;
      
    default:
      console.log('Mercure JWT Tokens for TechSci Labs');
      console.log('====================================');
      console.log();
      console.log('Secret:', MERCURE_JWT_SECRET);
      console.log();
      console.log('Publisher Token (for backend API):');
      console.log(generatePublisherJWT());
      console.log();
      console.log('Subscriber Token (for frontend):');
      console.log(generateSubscriberJWT());
      console.log();
      console.log('Usage:');
      console.log('  node generate-mercure-jwt.js publisher   # Publisher token only');
      console.log('  node generate-mercure-jwt.js subscriber  # Subscriber token only');
      console.log('  node generate-mercure-jwt.js user <id>   # User-specific token');
      break;
  }
}

module.exports = {
  generatePublisherJWT,
  generateSubscriberJWT,
  generateUserSubscriberJWT
};