# Coolify Deployment Checklist

1. Connect the GitHub repository to Coolify as a Docker Compose application.
2. Set the branch to `main`.
3. Use the root `docker-compose.yml`.
4. Assign the domain `https://kepoli.com` to service `wordpress`, port `80`.
5. Add persistent volumes created by Compose:
   - `kepoli_db`
   - `kepoli_wordpress`
   - `kepoli_uploads`
6. Add all required variables from `.env.example`.
7. Enable GitHub auto-deploy.
8. Run the seed command after first deploy and after content/theme changes:

```sh
docker compose run --rm wp-init
```

`wp-init` is intentionally one-shot. It has no public port and no healthcheck. The public service to monitor is `wordpress`.
