FROM node:22-bookworm-slim AS zing
WORKDIR /opt/musicbox-zing
COPY zing/package.json zing/package-lock.json ./
RUN npm ci --omit=dev --ignore-scripts --no-audit --no-fund --registry=https://registry.npmjs.org
COPY zing/server.cjs zing/bridge.cjs ./

FROM php:8.3-apache-bookworm
RUN apt-get update \
    && apt-get install -y --no-install-recommends libonig-dev ca-certificates \
    && docker-php-ext-install pdo_mysql mbstring \
    && a2enmod proxy proxy_http headers \
    && rm -rf /var/lib/apt/lists/*
COPY --from=zing /usr/local/bin/node /usr/local/bin/node
COPY --from=zing /opt/musicbox-zing /opt/musicbox-zing
COPY public/ /var/www/html/
COPY docker/apache.conf /etc/apache2/sites-available/000-default.conf
COPY docker/php.ini /usr/local/etc/php/conf.d/musicbox.ini
COPY docker/start.sh /usr/local/bin/musicbox-start
RUN chmod +x /usr/local/bin/musicbox-start
ENV PORT=10000 MUSICBOX_ZING_HOST=127.0.0.1 MUSICBOX_ZING_PORT=8877 MUSICBOX_LYRICS_SYNC_ENABLED=0
EXPOSE 10000
CMD ["/usr/local/bin/musicbox-start"]
