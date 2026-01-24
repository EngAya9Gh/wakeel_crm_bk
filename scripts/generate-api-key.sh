#!/bin/bash

# ============================================================================
# CRM Wakeel - API Key Generator
# ============================================================================
# This script generates a secure API key for the Public API
# ============================================================================

echo "🔐 CRM Wakeel - API Key Generator"
echo "=================================="
echo ""

# Generate a secure random API key (40 characters)
API_KEY=$(openssl rand -base64 30 | tr -d "=+/" | cut -c1-40)

echo "✅ Generated API Key:"
echo ""
echo "    $API_KEY"
echo ""
echo "📝 Next Steps:"
echo ""
echo "1. Add this key to your .env file:"
echo "   PUBLIC_API_KEYS=$API_KEY"
echo ""
echo "2. If you have multiple keys, separate them with commas:"
echo "   PUBLIC_API_KEYS=key1,key2,key3"
echo ""
echo "3. Send this key securely to the external developer"
echo ""
echo "⚠️  IMPORTANT: Keep this key secret and secure!"
echo ""
