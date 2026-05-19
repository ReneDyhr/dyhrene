#!/bin/sh
set -e

mkdir -p /var/www/html/storage/app/fontconfig-cache

exec "$@"
