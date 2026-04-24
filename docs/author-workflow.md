# Kepoli Author Workflow

Kepoli includes a small `kepoli-author-tools` plugin for writing posts.

## What Changes In WordPress Admin

- Posts use the classic WordPress editor, so `Add New Post` shows a clear title field and one main content editor.
- Pages keep the normal WordPress editor.
- The post editor toolbar includes:
  - `Pauza`: inserts a page break at the cursor.
  - `2 parti`: splits the current content into two WordPress pages.
  - `3 parti`: splits the current content into three WordPress pages.
- The Text editor tab also gets the same quick buttons.
- The `Kepoli post setup` box lets the writer choose `Reteta` or `Articol`, write an excerpt, write a meta description, and optionally adjust manual SEO, related links, image metadata, and recipe structured data.
- The setup box keeps one main action in front: `Completeaza automat`. Extra helper actions stay under `Mai multe unelte`, so the editor remains simple for day-to-day use.
- Manual SEO title and related-link slug fields now stay inside `Detalii SEO si legaturi`, so the main writing view stays focused on the essentials.
- The image and recipe sections are tucked into simple expandable blocks, so the writer sees the main writing fields first. `Date reteta` opens automatically only for recipe posts.
- The editorial checklist is also tucked into a simple expandable block, so the writer sees a short status first and opens the full list only when needed.
- Kepoli also tries to complete empty setup fields earlier in the flow: after the writer adds a real title, pastes enough content, switches to `Reteta`, or inserts one of the built-in templates. In the same phase, it can also replace the default category with a more likely one until the writer makes a manual category choice.
- The setup box also shows a live editorial checklist, so the writer can see what is still missing before publishing.
- If the writer saves a post with empty excerpt, meta description, related slugs, or featured-image metadata, Kepoli fills sensible defaults from the title/content and current post library.
- Seeded launch posts also read prefixed image metadata from `content/image-plan.json`, so the editor can show ready-made alt text, title, caption, and description even before the featured image is uploaded.
- The `Kepoli writing tools` box stays compact and focuses on two quick-start buttons: `Structura reteta` and `Structura articol`.
- A side box called `Kepoli publish companion` stays near Publish with one main action, `Pregateste pentru publicare`, plus optional details for category, tags, and the short review list.
- The `Posts` list includes `Tip Kepoli` and `Setup` columns, plus a Reteta/Articol filter for quick editorial audits.

## How To Use It

1. Go to `Posts` > `Add New`.
2. Add the title in the top field.
3. Write or paste the article/recipe in the main content field.
4. In `Kepoli post setup`, choose whether the post is a `Reteta` or `Articol`.
5. Start with the title and main content. Kepoli now tries to fill empty setup fields automatically once there is enough real content to work from.
6. Click `Completeaza automat` whenever you want Kepoli to do the main setup pass for you: SEO title, excerpt, meta description, internal links, image metadata, suggested category, suggested tags, and recipe schema where it can infer them.
7. Open `Mai multe unelte` only when you want a specific helper, like `Sugereaza categorie`, `Sugereaza taguri`, `Extrage schema reteta`, or `Genereaza meta imagine`.
8. Open `Detalii SEO si legaturi` only when you want to override the SEO title or manually edit related slugs.
9. Open `Detalii imagine` only when you want to review or refine the featured-image text. For recipes, `Date reteta` opens by itself when `Reteta` is selected.
10. For a long article, click `2 parti` or `3 parti` in the toolbar.
11. Open `Checklist editorial` only when you want the full list of missing items. The closed summary already tells you the current status.
12. When you are almost done, use `Pregateste pentru publicare` near Publish for one last automatic pass, then open `Vezi detalii` only if you want the category, tags, or the short review list.
13. Review the generated fields and inserted page breaks before publishing. If you publish with missing essentials, Kepoli will show a final warning.

## Image Workflow For Seeded Posts

1. Open `content/image-plan.json` and find the post slug.
2. Use the stored `prompt` in your image tool.
3. Export the final image in a web-friendly format, ideally `webp`, and save it into `content/images/` using the exact `filename` from the plan.
4. Push to GitHub and redeploy.
5. Kepoli imports the image, sets it as featured image, and applies the stored alt text, title, caption, and description automatically.

The split uses WordPress' native `<!--nextpage-->` marker. On the public post page, Kepoli shows a simple `Partile articolului` navigation block under the content.

## Notes

- Use splitting only for genuinely long posts. Short recipes usually read better on one page.
- After splitting, keep each page useful on its own: intro/context first, method/details next, conclusion/resources last.
- For AdSense readiness, avoid splitting posts only to increase ad views. Split only when it improves readability.
- Related slugs should be post URL slugs, for example `sarmale-in-foi-de-varza` or `ghidul-camarii-romanesti`. Kepoli can suggest them automatically, but the author should still remove weak matches.
- Excerptul este folosit in cardurile de postari, in arhive si in intro-ul paginii single. Chiar daca Kepoli il poate genera automat, merita sa-l ajustezi astfel incat sa sune natural si clar.
- For seeded launch posts, the image plan gives you a stronger starting point than the generic generator. The prompt stays in the repo workflow, not in the editor UI. If your final image differs from the planned composition, adjust the alt text before publishing.
- In the `Posts` list, `De completat` means the post is missing one or more useful editorial items such as meta description, excerpt, featured image, image alt text, internal links, or recipe schema.
