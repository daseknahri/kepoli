# Kepoli WordPress Blog

Kepoli is a GitHub-driven WordPress food blog for Romanian recipes and food articles. The repo contains the Docker Compose stack, custom theme, launch content, and WP-CLI bootstrap used by Coolify.

## What This Repo Builds

- WordPress with MariaDB, deployed by Docker Compose with small Kepoli-specific images built from this repo.
- A custom `kepoli` theme focused on reading, recipes, internal links, and ad-safe layouts.
- Production Apache settings for static asset caching, compression, and small security headers.
- An optional one-shot `wp-init` seed profile for manual reseeding.
- A small MU plugin that self-runs the same seed once if a platform starts WordPress but skips the one-shot seed service, and keeps the writer account as an administrator.
- A small authoring plugin that keeps post writing simple and adds toolbar buttons for splitting long posts into two or three WordPress pages.
- Google Site Kit installation for later AdSense, Search Console, and Analytics connection from WordPress admin.
- Comment and ping defaults tuned for a content-first launch, with seeded pages/posts closed to reduce spam overhead.

## Local Start

1. Copy `.env.example` to `.env` and set strong passwords.
2. Run:

```powershell
docker compose -f docker-compose.yml -f docker-compose.local.yml up -d
docker compose -f docker-compose.yml -f docker-compose.local.yml --profile seed run --rm wp-init
```

3. Open `http://localhost:8080`.

The seed is idempotent: rerunning `docker compose -f docker-compose.yml -f docker-compose.local.yml --profile seed run --rm wp-init` updates content by slug instead of duplicating it. For normal starts and Coolify deploys, the WordPress service self-seeds once from the baked-in `seed` and `content` folders.

## Coolify

1. Push this repo to GitHub.
2. In Coolify, create a Docker Compose application from the GitHub repo.
3. Add the environment variables from `.env.example`.
4. Assign `https://kepoli.com` to the `wordpress` service on port `80`.
5. Enable GitHub auto-deploy on push.
6. Do not enable the `seed` profile for normal Coolify deploys. WordPress self-seeds automatically from the app image.

The `CANONICAL_REDIRECT_HOSTS` value should include any extra hostnames that may reach the app, such as `www.kepoli.com`, `api.kepoli.com`, or `recipe.kepoli.com`. Kepoli redirects those hosts to `SITE_URL` so Search Console and readers see one canonical site.

If you need to manually reseed after launch, run:

```sh
docker compose --profile seed run --rm wp-init
```

Use only `docker-compose.yml` in Coolify. Do not add `docker-compose.local.yml`; that file publishes `localhost:8080` for local development only. Coolify should build the included `kepoli-wordpress` image from the repo so the theme, MU plugins, seed scripts, and content are copied into the running WordPress container.

## AdSense Notes

The theme contains inactive ad placements. Live ad markup is emitted only when `ADSENSE_ENABLE=1`, an AdSense client ID exists, and the matching slot environment variable is set. `ads.txt` is generated only when `ADSENSE_PUB_ID` is configured.

Before submitting to AdSense:

- Review all 30 posts for culinary accuracy and originality.
- Connect Google Site Kit from WordPress admin.
- Configure Google Privacy & Messaging or another Google-certified CMP for Romania, EEA, UK, and Switzerland traffic before personalized ads.
- Keep `ADSENSE_ENABLE=0` and `GA_ENABLE=0` until the consent layer is live and tested.
- Add your real AdSense client, publisher, and slot IDs in Coolify.

## Author Writing

The `kepoli-author-tools` plugin switches posts to the classic editor for a simpler title/content workflow. It adds `Pauza`, `2 parti`, and `3 parti` toolbar buttons for native WordPress post pagination, plus a Kepoli setup box for post type, excerpt, meta description, related slugs, featured-image metadata, and recipe structured data. The setup box can prefill excerpts, meta descriptions, internal-link slugs, and image metadata from the current post, and empty fields receive sensible defaults on save. The Posts list also shows Kepoli type/readiness columns for quick editorial checks. See `docs/author-workflow.md` for the exact writing flow.

## Media

The current repo includes SVG logo assets based on the provided Kepoli mark. If you want the exact uploaded bitmap images used instead, place them at:

- `wp-content/themes/kepoli/assets/img/kepoli-wordmark.png`
- `wp-content/themes/kepoli/assets/img/kepoli-icon.png`
- `wp-content/themes/kepoli/assets/img/writer-photo.jpg`

The theme automatically prefers those filenames when present.
