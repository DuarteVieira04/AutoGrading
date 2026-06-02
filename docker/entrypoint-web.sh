#!/usr/bin/env bash
set -euo pipefail

cd /app/submission-platform

export AUTOGRADING_PROJECT_ROOT="${AUTOGRADING_PROJECT_ROOT:-/app}"
export RUN_QUEUE_WORKER="${RUN_QUEUE_WORKER:-true}"

if [[ -z "${APP_KEY:-}" ]]; then
    echo "ERRO: APP_KEY em falta. Gera localmente com:" >&2
    echo "  cd submission-platform && php artisan key:generate --show" >&2
    echo "  e cola o valor em Environment → APP_KEY no Render." >&2
    exit 1
fi

php artisan migrate --force --no-interaction
php artisan storage:link --force 2>/dev/null || true
php artisan config:cache --no-interaction
php artisan route:cache --no-interaction
php artisan view:cache --no-interaction

envsubst '${PORT}' < /app/docker/nginx-render.conf.template > /etc/nginx/sites-enabled/default

exec /usr/bin/supervisord -c /app/docker/supervisord.conf
