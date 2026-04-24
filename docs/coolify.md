# Coolify Deployment Checklist

1. Connect the GitHub repository to Coolify as a Docker Compose application.
2. Set the branch to `main`.
3. Use the root `docker-compose.yml` only.
4. Let Coolify build the repo image `kepoli-wordpress`.
5. Assign the domain `https://kepoli.com` to service `wordpress`, port `80`.
6. Add persistent volumes created by Compose:
   - `kepoli_db`
   - `kepoli_wordpress`
   - `kepoli_uploads`
7. Add all required variables from `.env.example`.
8. Enable GitHub auto-deploy.
9. Leave the `seed` profile disabled for normal deploys. The `wordpress` container self-seeds automatically.

If a manual reseed is needed later, run:

```sh
docker compose --profile seed run --rm wp-init
```

`wp-init` is intentionally one-shot and is hidden behind the `seed` Compose profile so Coolify does not treat its clean exit as a failed deployment. The public service to monitor is `wordpress`.

Do not use `docker-compose.local.yml` in Coolify. That override publishes host port `8080` for local development and can fail on shared servers when the port is already allocated.

If Coolify skips or stops the one-shot service, the `wordpress` image already contains `seed` and `content`; the `kepoli-autoseed` MU plugin runs the seed once on the next request and activates the Kepoli theme.

For a temporary deploy check, set `KEPOLI_DEPLOY_FINGERPRINT=1`, redeploy, then verify the public site is actually on the current repo build:

```sh
node scripts/check-live-deploy.mjs https://kepoli.com
```

What the result means:

- `Live target` mismatch: Coolify is still serving an older image or did not redeploy the latest commit.
- `Live current` mismatch: the new code reached production, but the seed version on the live database did not catch up yet.
- Missing `kepoli-seed-*` meta tags: the fingerprint flag is disabled, or the public site is still on a build older than the deploy fingerprint update.

Turn `KEPOLI_DEPLOY_FINGERPRINT` back off after the check so normal production pages do not expose internal deployment details.
