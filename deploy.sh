#!/usr/bin/env bash
#
# Runs on the server after a release has been unpacked there.
# It installs nothing: vendor/ and public/build/ arrive already built.

set -euo pipefail
cd "$(dirname "$0")"

# The folder is left out on purpose: once the repository is public, so is this log.
echo "deploy: running on PHP $(php -r 'echo PHP_VERSION;')"

fail() {
    echo "deploy: $1" >&2
    exit 1
}

test -f vendor/autoload.php || fail "vendor/autoload.php is missing, so the release was not unpacked."
test -f public/build/manifest.json || fail "public/build/manifest.json is missing, so the release was not unpacked."
# The first release finds no .env, so one is written for the public demo.
# An .env that already exists is never touched.
if [ ! -f .env ]; then
    sed -e 's|^APP_ENV=.*|APP_ENV=production|' \
        -e 's|^APP_DEBUG=.*|APP_DEBUG=false|' \
        -e "s|^APP_URL=.*|APP_URL=${APP_URL:-https://leads.vinfetty.com}|" \
        -e 's|^DEMO_MODE=.*|DEMO_MODE=true|' \
        .env.example > .env
    php artisan key:generate --force --no-interaction
    echo "deploy: wrote a new .env for the demo"
fi

grep -q '^APP_KEY=base64:' .env || fail "APP_KEY is not set in .env. Run: php artisan key:generate"

mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs bootstrap/cache

if grep -q '^DB_CONNECTION=sqlite' .env && [ ! -f database/database.sqlite ]; then
    touch database/database.sqlite
fi

php artisan config:clear
php artisan migrate --force

# The demo starts every release from clean invented data.
if grep -q '^DEMO_MODE=true' .env; then
    php artisan demo:reset
fi

php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "deploy: done"
