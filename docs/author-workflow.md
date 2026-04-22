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
- The setup box includes automation buttons for `Genereaza excerpt`, `Genereaza meta description`, `Sugereaza linkuri interne`, and `Genereaza meta imagine`.
- If the writer saves a post with empty excerpt, meta description, related slugs, or featured-image metadata, Kepoli fills sensible defaults from the title/content and current post library.
- Seeded launch posts also read prefixed image metadata from `content/image-plan.json`, so the editor can show ready-made alt text, title, caption, description, and the AI prompt even before the featured image is uploaded.
- The `Kepoli writing tools` box includes quick buttons for inserting a recipe or article writing structure.
- The `Posts` list includes `Tip Kepoli` and `Setup` columns, plus a Reteta/Articol filter for quick editorial audits.

## How To Use It

1. Go to `Posts` > `Add New`.
2. Add the title in the top field.
3. Write or paste the article/recipe in the main content field.
4. In `Kepoli post setup`, choose whether the post is a `Reteta` or `Articol`.
5. Click `Genereaza excerpt`, `Genereaza meta description`, and `Sugereaza linkuri interne` if you want Kepoli to prefill the editorial summary, SEO text, and internal links from the pasted content.
6. Add a featured image, then click `Genereaza meta imagine` to prefill alt text, title, caption, and Media Library description.
7. For recipes, complete servings, prep/cook minutes, ingredients, and steps so Kepoli can output Recipe structured data.
8. For a long article, click `2 parti` or `3 parti` in the toolbar.
9. Review the generated fields and inserted page breaks before publishing.

## Image Workflow For Seeded Posts

1. Open `content/image-plan.json` and find the post slug.
2. Use the stored `prompt` in your image tool.
3. Save the final image into `content/images/` using the exact `filename` from the plan.
4. Push to GitHub and redeploy.
5. Kepoli imports the image, sets it as featured image, and applies the stored alt text, title, caption, and description automatically.

The split uses WordPress' native `<!--nextpage-->` marker. On the public post page, Kepoli shows a simple `Partile articolului` navigation block under the content.

## Notes

- Use splitting only for genuinely long posts. Short recipes usually read better on one page.
- After splitting, keep each page useful on its own: intro/context first, method/details next, conclusion/resources last.
- For AdSense readiness, avoid splitting posts only to increase ad views. Split only when it improves readability.
- Related slugs should be post URL slugs, for example `sarmale-in-foi-de-varza` or `ghidul-camarii-romanesti`. Kepoli can suggest them automatically, but the author should still remove weak matches.
- Excerptul este folosit in cardurile de postari, in arhive si in intro-ul paginii single. Chiar daca Kepoli il poate genera automat, merita sa-l ajustezi astfel incat sa sune natural si clar.
- For seeded launch posts, the image plan gives you a stronger starting point than the generic generator. If your final image differs from the planned composition, adjust the alt text before publishing.
- In the `Posts` list, `De completat` means the post is missing one or more useful editorial items such as meta description, excerpt, featured image, image alt text, internal links, or recipe schema.
