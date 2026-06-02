#!/usr/bin/env bash
# Normaliza variáveis de BD para PostgreSQL no Render.
set -euo pipefail

if [[ -n "${DATABASE_URL:-}" ]]; then
    case "${DATABASE_URL}" in
        postgres://*|postgresql://*)
            export DB_CONNECTION=pgsql
            ;;
        mysql://*)
            export DB_CONNECTION=mysql
            ;;
    esac
fi

export DB_CONNECTION="${DB_CONNECTION:-pgsql}"

# Render às vezes define DB_CONNECTION=autograding-db (nome do serviço) — Laravel precisa de pgsql|mysql|…
case "${DB_CONNECTION}" in
    mysql|pgsql|sqlite|sqlsrv) ;;
    *)
        if [[ -n "${DATABASE_URL:-}" ]] && [[ "${DATABASE_URL}" == postgres://* || "${DATABASE_URL}" == postgresql://* ]]; then
            export DB_CONNECTION=pgsql
        elif [[ -n "${DATABASE_URL:-}" ]] && [[ "${DATABASE_URL}" == mysql://* ]]; then
            export DB_CONNECTION=mysql
        else
            echo "AVISO: DB_CONNECTION=${DB_CONNECTION} inválido; a usar pgsql." >&2
            export DB_CONNECTION=pgsql
        fi
        ;;
esac

if [[ "${DB_CONNECTION}" == "pgsql" ]]; then
    if ! php -m 2>/dev/null | grep -qi '^pdo_pgsql$'; then
        echo "ERRO: DB_CONNECTION=pgsql mas a extensão pdo_pgsql não está instalada." >&2
        exit 1
    fi
fi
