#!/bin/sh
set -e

# Run database migrations (force = non-interactive, required for production)
php artisan migrate --force

# Start PHP development server on Render's injected PORT (default 8080)
exec php artisan serve --host=0.0.0.0 --port=${PORT:-8080}
