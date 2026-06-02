#!/usr/bin/env bash
set -euo pipefail

cd /app/submission-platform

export AUTOGRADING_PROJECT_ROOT="${AUTOGRADING_PROJECT_ROOT:-/app}"

if [[ -z "${APP_KEY:-}" ]]; then
    echo "APP_KEY em falta — define a mesma chave que no serviço web." >&2
    exit 1
fi

php artisan migrate --force --no-interaction

exec php artisan queue:work --sleep=3 --tries=1 --timeout=1900
