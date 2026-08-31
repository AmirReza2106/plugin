#!/bin/sh
set -eu

plugin_dir="/var/www/html/wp-content/plugins/workshop-registration"

if [ -f "${plugin_dir}/composer.json" ]; then
	composer install \
		--working-dir="${plugin_dir}" \
		--no-interaction \
		--prefer-dist
fi

exec docker-entrypoint.sh "$@"
