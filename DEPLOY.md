# DHAF Website — Deployment Guide

## Stack
- **Frontend**: pre-built React apps — public site in `public/`, admin in `admin/`
- **Backend**: PHP 8.2 + Apache (`api/`, `seo.php`)
- **Database**: Supabase PostgreSQL (the original package targeted MySQL; this repo is the PostgreSQL port)
- **Image uploads**: Supabase Storage (public bucket `media`)
- **Host**: Render (Docker web service)

There is no install wizard. On every start the container runs `db/bootstrap.php`,
which creates/upgrades the tables, imports the starting content into an empty
database, and creates the first admin account from environment variables.

---

## Step 1 — Create a Supabase project

Use a **new, empty** project. The startup script refuses to run against a
database that still has the old HSI site's tables.

You need two sets of values from it:
- **Project Settings → Database → Connection string → Transaction pooler**: host, port (6543), user, password
- **Project Settings → API**: Project URL and the `service_role` (or secret) key

## Step 2 — Set environment variables in Render

Render → your service → **Environment** (see `.env.example`):

| Key | Value |
|-----|-------|
| `DB_HOST` | pooler host, e.g. `aws-0-eu-west-2.pooler.supabase.com` |
| `DB_PORT` | `6543` |
| `DB_NAME` | `postgres` |
| `DB_USER` | `postgres.<project-ref>` |
| `DB_PASS` | database password |
| `DB_SSL` | `require` |
| `SUPABASE_URL` | `https://<project-ref>.supabase.co` |
| `SUPABASE_SERVICE_KEY` | service_role / secret key — keep private |
| `SITE_URL` | `https://<your-domain>` (no trailing slash) |
| `TRUST_PROXY` | `true` |
| `MAIL_FROM` | `noreply@<your-domain>` |
| `ADMIN_NAME`, `ADMIN_EMAIL`, `ADMIN_PASSWORD` | first admin account (password 10+ characters) |

`SUPABASE_SERVICE_KEY` matters: without it, uploaded images are saved on
Render's disk and disappear on the next deploy or restart.

## Step 3 — Deploy

Push to the branch Render deploys from. In the deploy logs you should see:

```
[bootstrap] Applied 14 migration(s).
[bootstrap] Imported the starting content.
[bootstrap] Created the admin account you@example.com ...
[bootstrap] Supabase Storage bucket "media" is ready.
```

Then **remove `ADMIN_PASSWORD`** from Render's environment.

## Step 4 — Custom domain

Render → service → **Settings → Custom Domains**: add the domain and follow the
DNS instructions. Make sure `SITE_URL` matches it.

## Step 5 — In the admin (`https://<your-domain>/admin/`)

- **Settings → Email**: SMTP details, then send a test email
- **Settings → Donations**: PayPal credentials (Live mode for real payments) or Donorbox / Paystack links
- **Settings → Integrations**: Google Analytics + Search Console, then submit `https://<your-domain>/sitemap.xml`
- Replace placeholder content (team bios, registered address, donation note)

---

## Maintenance

- **Reset a password / add an admin** (Render → Shell):
  `php db/create_admin.php "Full Name" "email@example.com" "new-password"`
- **Schema changes**: add a PostgreSQL file to `db/migrations/` (sorted by name);
  it runs automatically on the next deploy. Enable row level security on any new
  table (see `0013b_supabase_row_level_security.sql`).
- **Run locally**:
  ```bash
  docker build -t dhaf-site .
  docker run -p 8080:80 --env-file .env dhaf-site
  ```
  (For a local Postgres instead of Supabase, set `DB_SSL=disable`.)
