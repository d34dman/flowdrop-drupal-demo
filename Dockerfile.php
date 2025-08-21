ARG CLI_IMAGE
FROM ${CLI_IMAGE} as cli

FROM uselagoon/php-8.3-fpm:24.3.1

RUN apk update && apk --no-cache add \
  freetype-dev libjpeg-turbo-dev libpng-dev libxpm-dev libwebp-dev \
  libavif-dev aom-dev dav1d-dev \
  jpegoptim optipng pngquant
RUN docker-php-ext-configure gd --enable-gd --with-webp --with-jpeg --with-xpm --with-freetype --with-avif
RUN docker-php-ext-install -j"$(nproc)" gd

COPY --from=cli /app /app
