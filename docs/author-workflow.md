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
- The `Kepoli post setup` box lets the writer choose `Reteta` or `Articol`, write an excerpt, write a meta description, add related post slugs, complete featured-image metadata, and complete recipe structured data.
- The setup box includes automation buttons for `Completeaza automat`, `Extrage schema reteta`, `Genereaza excerpt`, `Genereaza meta description`, `Sugereaza linkuri interne`, and `Genereaza meta imagine`.
- The setup box also shows a live editorial checklist, so the writer can see what is still missing before publishing.
- If the writer saves a post with empty excerpt, meta description, related slugs, or featured-image metadata, Kepoli fills sensible defaults from the title/content and current post library.
- Seeded launch posts also read prefixed image metadata from `content/image-plan.json`, so the editor can show ready-made alt text, title, caption, and description even before the featured image is uploaded.
- The `Kepoli writing tools` box includes quick buttons for inserting a recipe or article writing structure.
- The `Posts` list includes `Tip Kepoli` and `Setup` columns, plus a Reteta/Articol filter for quick editorial audits.

## How To Use It

1. Go to `Posts` > `Add New`.
2. Add the title in the top field.
3. Write or paste the article/recipe in the main content field.
4. In `Kepoli post setup`, choose whether the post is a `Reteta` or `Articol`.
5. Click `Completeaza automat` to fill the common empty fields in one pass: SEO title, excerpt, meta description, internal links, image metadata, and recipe schema where Kepoli can infer it.
6. Add a featured image, then click `Genereaza meta imagine` if you want to refresh alt text, title, caption, and Media Library description specifically for that image.
7. For recipes, click `Extrage schema reteta` after you write the structured content. Kepoli will look for sections like `Ingrediente` and `Mod de preparare` and move them into the recipe schema fields.
8. For a long article, click `2 parti` or `3 parti` in the toolbar.
9. Watch the live checklist in `Kepoli post setup` and complete any missing items.
10. Review the generated fields and inserted page breaks before publishing. If you publish with missing essentials, Kepoli will show a final warning.

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
