# Continue stage build with the desired image and copy the source including the dependencies downloaded by composer
FROM uselagoon/php-8.3-cli-drupal:24.3.1 AS cli

RUN apk update && apk --no-cache add \
  freetype-dev libjpeg-turbo-dev libpng-dev libxpm-dev libwebp-dev \
  libavif-dev aom-dev dav1d-dev \
  jpegoptim optipng pngquant
RUN docker-php-ext-configure gd --enable-gd --with-webp --with-jpeg --with-xpm --with-freetype --with-avif
RUN docker-php-ext-install -j"$(nproc)" gd


# Copying the source directory and install the dependencies with composer
COPY composer.json composer.lock /app/
COPY ./patches /app/patches

WORKDIR /app

# Run composer install to install the dependencies
RUN composer install --no-dev

# Continue stage build with the desired image and copy the source including the dependencies downloaded by composer
FROM uselagoon/nginx-drupal:24.3.1
# https://github.com/uselagoon/lagoon-images/blob/main/images/nginx-drupal/Dockerfile
# https://github.com/uselagoon/lagoon-images/blob/main/images/nginx/Dockerfile
# https://github.com/uselagoon/lagoon-images/blob/main/images/commons/Dockerfile

COPY --from=cli /app /app

# Define where the Drupal Root is located
ENV WEBROOT=web
