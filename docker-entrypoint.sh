#!/bin/sh
set -e

# Run database migrations (force = non-interactive, required for production)
# If DB is not accessible, log and continue (container can still serve requests)
php artisan migrate --force || echo "⚠️  Migration failed — continuing startup"

# Run package discovery with all env vars available
php artisan package:discover --ansi || true

# Start FrankenPHP
exec frankenphp run --config /etc/frankenphp/Caddyfile
