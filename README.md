# Kepoli WordPress Blog

Kepoli is a GitHub-driven WordPress food blog for Romanian recipes and kitchen articles. This repo contains the live site stack, content seed, custom theme, and the clone tooling used to launch future sibling food blogs from the same engine.

For repeatable cloning and launch steps, use `docs/new-blog-launch-plan.md`. For deeper clone details, use `docs/replicate-food-blog.md`. For a fresh Codex handoff prompt, use `docs/codex-new-site-prompt.md`. For current operating status, use `docs/project-status.md`. The most robust path is: create `site-brief.json` with `node scripts/create-site-brief.mjs ... --write`, run `node scripts/start-new-blog.mjs --brief site-brief.json --write`, then run `node scripts/validate-new-blog.mjs --brief site-brief.json`. When changing the shared engine itself, run `node scripts/audit-engine-readiness.mjs` before using it for another clone.

## What This Repo Builds

- WordPress with MariaDB, deployed by Docker Compose with small project-specific images built from this repo.
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

The seed is idempotent for a fresh launch, but after the site is live it should not be used as a normal update mechanism. For normal starts and Coolify deploys, the WordPress service self-seeds only on an empty install from the baked-in `seed` and `content` folders.

## Coolify

1. Push this repo to GitHub.
2. In Coolify, create a Docker Compose application from the GitHub repo.
3. Add the environment variables from `.env.example`.
4. Assign `https://kepoli.com` to the `wordpress` service on port `80`.
5. Enable GitHub auto-deploy on push.
6. Do not enable the `seed` profile for normal Coolify deploys. WordPress self-seeds only on first launch, before real content exists.

The `CANONICAL_REDIRECT_HOSTS` value should include any extra hostnames that may reach the app, such as `www.kepoli.com`, `api.kepoli.com`, or `recipe.kepoli.com`. The MU plugin redirects those hosts to `SITE_URL` so Search Console and readers see one canonical site.

After launch, keep:

```env
KEPOLI_AUTOSEED_ENABLE=1
KEPOLI_FORCE_RESEED=0
```

This allows a fresh empty install to self-seed once, while protecting manual posts, manual images, post dates, and live content on normal redeploys. If you truly need to manually reseed after launch, set `KEPOLI_FORCE_RESEED=1` temporarily, redeploy or run:

```sh
docker compose --profile seed run --rm wp-init
```

Then set `KEPOLI_FORCE_RESEED=0` immediately after the repair.

Use only `docker-compose.yml` in Coolify. Do not add `docker-compose.local.yml`; that file publishes `localhost:8080` for local development only. Coolify should build the included `kepoli-wordpress` image from the repo so the theme, MU plugins, seed scripts, and content are copied into the running WordPress container.

## AdSense Notes

The theme contains inactive ad placements. Live ad markup is emitted only when `ADSENSE_ENABLE=1`, an AdSense client ID exists, and the matching slot environment variable is set. `ads.txt` serves the configured AdSense publisher record when `ADSENSE_PUB_ID` is set, or redirects to Ezoic ads.txt management when `EZOIC_ADSTXT_ACCOUNT_ID` or `EZOIC_ADSTXT_REDIRECT_URL` is configured.

The newsletter signup is a small native WordPress form on the front page and the About Kepoli page. Signups are stored in WordPress admin under `Newsletter`, where you can review them or export a CSV and follow up manually from `contact@kepoli.com` without adding a paid newsletter service.

The MU plugin also serves `/.well-known/security.txt` with contact and canonical details, alongside `ads.txt` and canonical-host redirects.

Before submitting to AdSense:

- Review all 30 posts for culinary accuracy and originality.
- Connect Google Site Kit from WordPress admin.
- Configure Google Privacy & Messaging or another Google-certified CMP for Romania, EEA, UK, and Switzerland traffic before personalized ads.
- Keep `ADSENSE_ENABLE=0` and `GA_ENABLE=0` until the consent layer is live and tested.
- Add your real AdSense client, publisher, and slot IDs in Coolify.

## Author Writing

The `kepoli-author-tools` plugin switches posts to the classic editor for a simpler title/content workflow. It adds `Pauza`, `2 parti`, and `3 parti` toolbar buttons for native WordPress post pagination, plus a post setup box for post type, excerpt, meta description, related slugs, featured-image metadata, and recipe structured data. The setup box can prefill excerpts, meta descriptions, internal-link slugs, and image metadata from the current post, and empty fields receive sensible defaults on save. The Posts list also shows type/readiness columns for quick editorial checks. See `docs/author-workflow.md` for the exact writing flow.

## Media

The current repo includes the Kepoli SVG logo assets. If you want to use the bitmap images instead, place them at:

- `wp-content/themes/kepoli/assets/img/kepoli-wordmark.png`
- `wp-content/themes/kepoli/assets/img/kepoli-icon.png`
- `wp-content/themes/kepoli/assets/img/writer-photo.jpg`

The theme automatically prefers those filenames when present.
