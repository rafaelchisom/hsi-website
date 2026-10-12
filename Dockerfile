# ── DHAF website — PHP 8.2 + Apache on Render, database on Supabase ──────────
FROM php:8.2-apache

# PostgreSQL driver, GD (image resizing for uploads), zip
RUN apt-get update && apt-get install -y --no-install-recommends \
    libpq-dev \
    libpng-dev \
    libjpeg62-turbo-dev \
    libwebp-dev \
    libzip-dev \
  && docker-php-ext-configure gd --with-jpeg --with-webp \
  && docker-php-ext-install pdo_pgsql gd zip \
  && apt-get clean && rm -rf /var/lib/apt/lists/*

# Production PHP settings, with room for photo uploads (the app downsizes them)
RUN cp "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini" \
  && printf 'upload_max_filesize = 12M\npost_max_size = 14M\nexpose_php = Off\n' > "$PHP_INI_DIR/conf.d/dhaf.ini"

# .htaccess routing, security headers, compression and caching
RUN a2enmod rewrite headers deflate expires \
  && sed -i 's|AllowOverride None|AllowOverride All|g' /etc/apache2/apache2.conf

# Each Apache worker keeps one persistent database connection (see
# api/lib/Database.php), so cap the workers: this bounds the connections to the
# Supabase pooler (shared with other apps) and memory on Render's instance.
RUN printf '<IfModule mpm_prefork_module>\n  StartServers 4\n  MinSpareServers 4\n  MaxSpareServers 10\n  MaxRequestWorkers 25\n  MaxConnectionsPerChild 2000\n</IfModule>\nKeepAliveTimeout 2\n' \
    > /etc/apache2/conf-available/dhaf-workers.conf \
  && a2enconf dhaf-workers

COPY . /var/www/html/
RUN mv /var/www/html/docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh \
  && chmod +x /usr/local/bin/docker-entrypoint.sh \
  && mkdir -p /var/www/html/api/uploads \
  && chown -R www-data:www-data /var/www/html

EXPOSE 80
ENTRYPOINT ["docker-entrypoint.sh"]
