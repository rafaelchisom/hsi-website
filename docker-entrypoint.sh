#!/bin/sh
# Runs on every container start, before Apache.
set -e
cd /var/www/html

# Fill in the public address in the static files that need an absolute URL.
if [ -n "$SITE_URL" ]; then
  url=$(printf '%s' "$SITE_URL" | sed 's:/*$::')
  sed -i "s|@@SITE_URL@@|$url|g" public/robots.txt public/index.html
fi

# Create/upgrade the database tables, first-run content and admin account.
# A failure is logged but doesn't stop the site from starting.
php db/bootstrap.php || echo "[bootstrap] Database setup did not complete — see the error above."

exec apache2-foreground
