# ── HSI Website — PHP 8.2 + Apache on Render ─────────────────────────────────
FROM php:8.2-apache

# Install PostgreSQL PDO driver + zip for uploads
RUN apt-get update && apt-get install -y \
    libpq-dev \
    libzip-dev \
    zip \
    unzip \
  && docker-php-ext-install \
    pdo \
    pdo_pgsql \
    zip \
  && apt-get clean && rm -rf /var/lib/apt/lists/*

# Enable Apache mod_rewrite (needed for .htaccess routing)
RUN a2enmod rewrite

# Allow .htaccess overrides in the web root
RUN sed -i 's|AllowOverride None|AllowOverride All|g' /etc/apache2/apache2.conf

# Copy all site files into the web root
COPY . /var/www/html/

# Fix permissions — Apache needs to read/write uploads/
RUN chown -R www-data:www-data /var/www/html \
  && chmod -R 755 /var/www/html \
  && chmod -R 775 /var/www/html/uploads

# Remove .DS_Store files (Mac artefacts)
RUN find /var/www/html -name ".DS_Store" -delete

# Expose port 80 (Render maps this automatically)
EXPOSE 80

CMD ["apache2-foreground"]
