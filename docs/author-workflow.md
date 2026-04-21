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
- The `Kepoli post setup` box lets the writer choose `Reteta` or `Articol`, write a meta description, add related post slugs, and complete recipe structured data.
- The `Kepoli writing tools` box includes quick buttons for inserting a recipe or article writing structure.

## How To Use It

1. Go to `Posts` > `Add New`.
2. Add the title in the top field.
3. Write or paste the article/recipe in the main content field.
4. In `Kepoli post setup`, choose whether the post is a `Reteta` or `Articol`.
5. Add a short meta description and related slugs for internal linking.
6. For recipes, complete servings, prep/cook minutes, ingredients, and steps so Kepoli can output Recipe structured data.
7. For a long article, click `2 parti` or `3 parti` in the toolbar.
8. Review the inserted page breaks before publishing.

The split uses WordPress' native `<!--nextpage-->` marker. On the public post page, Kepoli shows a simple `Partile articolului` navigation block under the content.

## Notes

- Use splitting only for genuinely long posts. Short recipes usually read better on one page.
- After splitting, keep each page useful on its own: intro/context first, method/details next, conclusion/resources last.
- For AdSense readiness, avoid splitting posts only to increase ad views. Split only when it improves readability.
- Related slugs should be post URL slugs, for example `sarmale-in-foi-de-varza` or `ghidul-camarii-romanesti`.
