ARG DOCKER_REGISTRY=registry.docker.ir

FROM ${DOCKER_REGISTRY}/library/wordpress:php8.3-fpm

RUN apt-get update \
	&& apt-get install --no-install-recommends --yes ca-certificates curl git unzip \
	&& curl --fail --location --silent --show-error https://getcomposer.org/installer --output /tmp/composer-setup.php \
	&& curl --fail --location --silent --show-error https://composer.github.io/installer.sig --output /tmp/composer-setup.sig \
	&& php -r "exit(hash_file('sha384', '/tmp/composer-setup.php') === trim(file_get_contents('/tmp/composer-setup.sig')) ? 0 : 1);" \
	&& php /tmp/composer-setup.php --install-dir=/usr/local/bin --filename=composer \
	&& rm /tmp/composer-setup.php /tmp/composer-setup.sig \
	&& rm -rf /var/lib/apt/lists/*

COPY docker/entrypoint.sh /usr/local/bin/workshop-entrypoint
COPY docker/php/workshop.ini /usr/local/etc/php/conf.d/workshop.ini

RUN chmod 0755 /usr/local/bin/workshop-entrypoint

ENV COMPOSER_ALLOW_SUPERUSER=1
ENV COMPOSER_HOME=/tmp/composer
ENV COMPOSER_CACHE_DIR=/tmp/composer-cache
ENV COMPOSER_ROOT_VERSION=0.1.0

ENTRYPOINT ["workshop-entrypoint"]
CMD ["php-fpm"]
