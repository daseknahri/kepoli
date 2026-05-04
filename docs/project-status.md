# Project Status

This is the handoff note for future work on Kepoli.

## Current Role

Kepoli is the Romanian AdSense-clean site. Keep it conservative while AdSense approval is pending.

## Production Defaults

```env
ADSENSE_ENABLE=0
GA_ENABLE=0
KEPOLI_AUTOSEED_ENABLE=1
KEPOLI_FORCE_RESEED=0
HISTATS_ENABLE=1
HISTATS_EXCLUDE_ADMINS=1
```

Do not add Monetag, Adsterra, popups, forced redirects, or aggressive ads to Kepoli before AdSense approval.

## Content Workflow

- Admin stays English.
- Public content stays Romanian.
- Use the external AI prompt to generate only title and clean plain-text content.
- In WordPress, choose `Reteta` or `Articol`, then use `Completeaza automat`.
- For long posts, use `Impartire automata` or `2 parti` / `3 parti`.
- Smart split is intentionally conservative: `650+` words for 2 parts and `1300+` words for 3 parts.

## Deployment Rules

- Normal redeploys must not reseed content.
- Keep `KEPOLI_FORCE_RESEED=0` unless intentionally repairing seed data.
- If a repair needs reseed, set `KEPOLI_FORCE_RESEED=1` temporarily, run the repair, then immediately set it back to `0`.

## Checks Before Push

```powershell
node scripts\verify-content.mjs
node scripts\audit-adsense-readiness.mjs
node scripts\audit-engine-readiness.mjs
git diff --check
```

If a live deploy needs verification, temporarily set `KEPOLI_DEPLOY_FINGERPRINT=1`, redeploy, run `node scripts\check-live-deploy.mjs https://kepoli.com`, then turn the fingerprint off.

## Key Docs

- `docs/ai-content-growth-strategy.md`: future AI, content, Facebook, SEO, and monetization direction.
- `docs/author-workflow.md`: posting, auto-fill, and auto-split workflow.
- `docs/adsense-readiness.md`: AdSense-safe checks and policy guardrails.
- `docs/new-blog-launch-plan.md`: repeatable new-site workflow.
- `docs/replicate-food-blog.md`: clone/rebrand process.
