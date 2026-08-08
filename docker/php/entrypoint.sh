#!/usr/bin/env bash
set -e

if [ ! -f vendor/autoload.php ]; then
    composer install --no-interaction --prefer-dist
fi

# storage/ e bootstrap/cache/ vêm do bind mount do host (dono != www-data);
# sem isso, qualquer escrita de log/view/cache real do PHP-FPM falha com
# "Permission denied" mesmo com o health-check /up respondendo normalmente.
# Só diretórios são alterados (nunca os arquivos .gitignore versionados).
find storage bootstrap/cache -type d -exec chmod 0777 {} +

if [ -f .env ] && ! grep -q '^APP_KEY=.\+' .env; then
    php artisan key:generate --force
fi

if [ "$1" = "php-fpm" ]; then
    php artisan migrate --force
fi

exec "$@"
