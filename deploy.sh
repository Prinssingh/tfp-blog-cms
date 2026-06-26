#!/bin/bash
# Run this on Hostinger via SSH after cloning

set -e

echo "Pulling latest code..."
git pull origin main

echo "Installing dependencies..."
composer install --no-dev --optimize-autoloader

echo "Setting permissions..."
chmod -R 755 storage/
chmod -R 755 uploads/
chmod 644 .env

echo "Done. API is live."
