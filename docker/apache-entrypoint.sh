#!/usr/bin/env bash
set -e

# Soporte dinámico de variable PORT para plataformas Cloud (Render, Railway, Zeabur, Hugging Face)
LISTEN_PORT="${PORT:-80}"

if [ "$LISTEN_PORT" != "80" ]; then
    sed -ri "s/Listen 80/Listen ${LISTEN_PORT}/g" /etc/apache2/ports.conf
    sed -ri "s/<VirtualHost \*:80>/<VirtualHost *:${LISTEN_PORT}>/g" /etc/apache2/sites-available/000-default.conf
fi

exec apache2-foreground