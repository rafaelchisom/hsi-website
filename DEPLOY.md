# HSI Website — Deployment Guide

## Stack
- **Frontend**: React/Vite SPA (pre-built, served via PHP)
- **Backend**: PHP 8.2 + Apache
- **Database**: Supabase PostgreSQL
- **Host**: Render (Docker web service)

---

## Step 1 — Run the schema in Supabase

1. Go to **Supabase dashboard** → your project → **SQL Editor**
2. Paste the contents of `api/schema_pg.sql` and run it
3. This creates all tables and seeds default content

---

## Step 2 — Push to GitHub

```bash
git init          # if not already a repo
git add .
git commit -m "HSI website initial deploy"
git remote add origin https://github.com/YOUR_USERNAME/hsi-website.git
git push -u origin main
```

> Make sure `.env` is in `.gitignore` (it is) — never commit real credentials.

---

## Step 3 — Create a Render Web Service

1. Go to **render.com** → New → **Web Service**
2. Connect your GitHub repo
3. Set these settings:
   - **Environment**: Docker
   - **Branch**: main
   - **Dockerfile path**: `./Dockerfile` (auto-detected)
   - **Region**: pick closest to your users

---

## Step 4 — Set Environment Variables in Render

In Render → your service → **Environment**, add:

| Key | Value |
|-----|-------|
| `DB_HOST` | From Supabase (see below) |
| `DB_PORT` | `6543` |
| `DB_NAME` | `postgres` |
| `DB_USER` | `postgres.xxxxxxxxxxxx` (your project ref) |
| `DB_PASS` | Your Supabase DB password |
| `DB_SSL` | `require` |
| `ALLOWED_ORIGIN_PROD` | `https://healthsystemsinitiative.org` |

### Getting Supabase connection details
Supabase dashboard → **Project Settings** → **Database** → **Connection string**

Use the **Transaction pooler** (Session mode also works):
- Host: `aws-0-eu-west-2.pooler.supabase.com` (varies by region)
- Port: `6543`
- User: `postgres.[your-project-ref]`

---

## Step 5 — Run the install wizard

Once deployed, visit:
```
https://your-render-url.onrender.com/install.php
```

- Fill in your Supabase credentials and create your admin username/password
- This writes a `.env` file to the server and creates the admin user
- **Delete `install.php` from your repo immediately after** — the site won't load until you do

```bash
rm install.php
git add install.php
git commit -m "Remove install.php after setup"
git push
```

---

## Step 6 — Set custom domain

In Render → your service → **Settings** → **Custom Domain**
Add `healthsystemsinitiative.org` and follow the DNS instructions.

---

## Admin panel
Once live: `https://healthsystemsinitiative.org/admin`
