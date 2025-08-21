ARG FE_IMAGE
FROM ${FE_IMAGE} as frontend

FROM uselagoon/php-8.3-cli-drupal:24.3.1

RUN apk update && apk --no-cache add \
  freetype-dev libjpeg-turbo-dev libpng-dev libxpm-dev libwebp-dev \
  libavif-dev aom-dev dav1d-dev \
  jpegoptim optipng pngquant
RUN docker-php-ext-configure gd --enable-gd --with-webp --with-jpeg --with-xpm --with-freetype --with-avif
RUN docker-php-ext-install -j"$(nproc)" gd

COPY composer.json composer.lock /app/
COPY ./patches /app/patches
RUN composer install --no-dev
COPY . /app
COPY --from=frontend /app/build /app/web/themes/custom/circle_dot/build
COPY --from=frontend /app/src /app/web/themes/custom/circle_dot/src
RUN rm -rf /home/.drush/drush.yml && rm -rf /home/.drush/drushrc.php
# Define where the Drupal Root is located
ENV WEBROOT=web
