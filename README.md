# Kepoli WordPress Blog

Kepoli is a GitHub-driven WordPress food blog for Romanian recipes and food articles. The repo contains the Docker Compose stack, custom theme, launch content, and WP-CLI bootstrap used by Coolify.

## What This Repo Builds

- WordPress with MariaDB, deployed by Docker Compose.
- A custom `kepoli` theme focused on reading, recipes, internal links, and ad-safe layouts.
- A one-shot `wp-init` service that installs/configures WordPress and seeds 30 published posts plus required AdSense-readiness pages.
- Google Site Kit installation for later AdSense, Search Console, and Analytics connection from WordPress admin.

## Local Start

1. Copy `.env.example` to `.env` and set strong passwords.
2. Run:

```powershell
docker compose -f docker-compose.yml -f docker-compose.local.yml up -d
docker compose -f docker-compose.yml -f docker-compose.local.yml run --rm wp-init
```

3. Open `http://localhost:8080`.

The seed is idempotent: rerunning `docker compose -f docker-compose.yml -f docker-compose.local.yml run --rm wp-init` updates content by slug instead of duplicating it.

## Coolify

1. Push this repo to GitHub.
2. In Coolify, create a Docker Compose application from the GitHub repo.
3. Add the environment variables from `.env.example`.
4. Assign `https://kepoli.com` to the `wordpress` service on port `80`.
5. Enable GitHub auto-deploy on push.
6. After each deploy, run the one-shot `wp-init` service or configure a post-deploy command equivalent to:

```sh
docker compose run --rm wp-init
```

Use only `docker-compose.yml` in Coolify. Do not add `docker-compose.local.yml`; that file publishes `localhost:8080` for local development only.

## AdSense Notes

The theme contains inactive ad placements. Live ad markup is only emitted when both an AdSense client ID and the matching slot environment variable are set. `ads.txt` is generated only when `ADSENSE_PUB_ID` is configured.

Before submitting to AdSense:

- Review all 30 posts for culinary accuracy and originality.
- Connect Google Site Kit from WordPress admin.
- Configure Privacy & Messaging or another Google-certified CMP for EEA/UK/Switzerland traffic before personalized ads.
- Add your real AdSense client, publisher, and slot IDs in Coolify.

## Media

The current repo includes SVG logo assets based on the provided Kepoli mark. If you want the exact uploaded bitmap images used instead, place them at:

- `wp-content/themes/kepoli/assets/img/kepoli-wordmark.png`
- `wp-content/themes/kepoli/assets/img/kepoli-icon.png`
- `wp-content/themes/kepoli/assets/img/writer-photo.jpg`

The theme automatically prefers those filenames when present.
