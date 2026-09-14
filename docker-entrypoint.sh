#!/bin/sh
set -e

# Run package discovery at runtime with all env vars available
php artisan package:discover --ansi || true

# Start FrankenPHP
exec frankenphp run --config /etc/frankenphp/Caddyfile
