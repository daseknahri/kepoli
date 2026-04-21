# Coolify Deployment Checklist

1. Connect the GitHub repository to Coolify as a Docker Compose application.
2. Set the branch to `main`.
3. Use the root `docker-compose.yml` only.
4. Let Coolify build both repo images: `kepoli-wordpress` and `kepoli-wp-cli`.
5. Assign the domain `https://kepoli.com` to service `wordpress`, port `80`.
6. Add persistent volumes created by Compose:
   - `kepoli_db`
   - `kepoli_wordpress`
   - `kepoli_uploads`
7. Add all required variables from `.env.example`.
8. Enable GitHub auto-deploy.
9. Run the seed command after first deploy and after content/theme changes:

```sh
docker compose run --rm wp-init
```

`wp-init` is intentionally one-shot. It has no public port and no healthcheck. The public service to monitor is `wordpress`.

Do not use `docker-compose.local.yml` in Coolify. That override publishes host port `8080` for local development and can fail on shared servers when the port is already allocated.

If Coolify skips or stops the one-shot service, the `wordpress` image already contains `seed` and `content`; the `kepoli-autoseed` MU plugin runs the seed once on the next request and activates the Kepoli theme.
