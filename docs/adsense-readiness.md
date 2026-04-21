# AdSense Readiness Notes

This project prepares the site for AdSense review, but Google makes the final approval decision.

Implemented readiness items:

- Public pages for privacy, cookies, terms, culinary disclaimer, contact, author, and about.
- Thirty published Romanian food posts at launch.
- Clear category navigation and internal links.
- Ad placeholders that preserve layout before ad units exist.
- Live ad rendering gated by environment variables, including an explicit `ADSENSE_ENABLE` switch.
- `ads.txt` generated only after `ADSENSE_PUB_ID` is configured.
- Google Site Kit installed for later account connection.
- Dedicated public page for `Publicitate si consimtamant`.

Before applying:

- Replace any temporary media with final brand/author images if desired.
- Review generated recipes for correctness and originality.
- Connect Site Kit to the Google account that owns AdSense/Search Console/Analytics.
- Configure Google Privacy & Messaging or a Google-certified CMP for Romania, EEA, UK, and Switzerland visitors before showing personalized ads.
- Keep `ADSENSE_ENABLE=0` until the consent flow is live, includes a link to Google's Business Data Responsibility site, and has been tested on the live domain.
