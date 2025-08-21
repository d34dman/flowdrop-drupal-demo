FROM ghcr.io/d34dman/drupal-cli:main AS cli

COPY composer.json composer.lock /app/
COPY ./patches /app/patches
RUN composer install --no-dev
COPY . /app
RUN rm -rf /home/.drush/drush.yml && rm -rf /home/.drush/drushrc.php
# Define where the Drupal Root is located
ENV WEBROOT=web

FROM ghcr.io/d34dman/drupal-php:main

COPY --from=cli /app /app
