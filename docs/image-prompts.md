# Kepoli — Image Generation Prompts

Goal: authentic, warm, home-cook food photography that reads as **real** (not stocky, not obviously AI). This directly addresses the AdSense "originality / who made this" signal — so lean rustic and imperfect, not glossy-perfect.

## How to use
- Paste **[PROMPT] + the STYLE SUFFIX** into your image tool (Midjourney, DALL·E, Flux, etc.).
- Generate the **featured/hero** image for every post (landscape). Where noted, also generate **1–2 process shots** per recipe — multiple real-looking photos per post is a strong authenticity signal.
- Export **WebP**, landscape **3:2 (e.g. 1600×1066)** for heroes. Keep file names = the post slug (e.g. `hearty-bean-soup.webp`) and drop them into `content/images/` replacing the current placeholders.
- Keep the look **consistent across all 20** so the site feels like one photographer/kitchen.
- Author portrait: use a **real, consented photo if at all possible** — a generated face is a weak trust signal and, ideally, a real person should stand behind the byline.

## STYLE SUFFIX (append to every food prompt)
> Photorealistic home food photography, soft natural window light from the side, shot on a 50mm lens at f/2.8 with gentle shallow depth of field, cozy lived-in home kitchen, muted earthy palette (cream, warm browns, soft sage green), rustic stoneware and worn wooden board, a few honest imperfections (a stray crumb, a drip on the rim, faint rising steam), realistic home-size portion, matte not glossy, no text, no watermark, no logos, 3:2 landscape, high detail.

Negative (if your tool supports it): `text, watermark, logo, extra utensils clutter, plastic look, oversaturated, cartoon, illustration, deformed hands, duplicated food`.

---

## Recipes (hero images)

**classic-cabbage-rolls** — A rustic pot of classic cabbage rolls, tender pale-green cabbage parcels nestled in a rich red tomato sauce, one roll cut open to show the pork-and-rice filling, a sprig of dill and a small dish of sour cream beside it.

**easy-stuffed-peppers** — Four halved red and yellow bell peppers filled with rice-and-meat, baked in a shallow dish in a light tomato sauce, tops lightly browned, fresh parsley scattered on top.

**homestyle-apple-pie** — A golden double-crust apple pie on a wooden board, one slice lifted out showing soft cinnamon-spiced apple filling, a little juice pooling, a worn table knife alongside.

**hearty-bean-soup** — A deep bowl of thick white bean soup with chunks of smoked pork and diced carrot and celery, a swirl of oil on top, chopped parsley, crusty bread torn beside it, spoon resting in the bowl.

**sour-meatball-soup** — A bowl of clear-golden sour meatball soup, small tender meatballs and diced carrot, celery and parsnip suspended in the broth, fresh dill and parsley on top, a lemon wedge on the rim.

**chicken-soup-with-dumplings** — A bowl of clear golden chicken soup with soft fluffy semolina dumplings floating on top, rounds of carrot, a little parsley, gentle steam rising.

**chicken-stew-with-polenta** — Bone-in braised chicken in a glossy paprika-red tomato-and-pepper sauce, spooned over a mound of soft creamy polenta on a rustic plate, a scatter of parsley.

**braised-cabbage-with-sausage** — A cast-iron pan of silky slow-braised shredded cabbage gone golden and sweet, with browned slices of smoked sausage tucked through it, a dusting of paprika.

**white-bean-stew** — A bowl of thick creamy white bean stew flecked with paprika and softened onion, a drizzle of olive oil and chopped parsley on top, crusty bread at the edge.

**crepes-with-jam** — A plate of thin rolled crêpes filled with red fruit jam, one crêpe partly unrolled to show the glossy jam, a light dusting of powdered sugar, a spoon of jam beside them.

**no-bake-chocolate-biscuit-salami** — A dark chocolate biscuit "salami" log partly sliced into rounds on parchment, the cut faces showing a mosaic of pale biscuit pieces in dark chocolate, one round leaning against the log.

## Story / lifestyle (hero images)

**the-sunday-table** — A relaxed family Sunday table from above, several shared home-cooked dishes (a braise, bread, a salad, a jug of water), mismatched plates and worn linen napkins, warm afternoon light, no people.

**grandmothers-recipe-card** — A slice of sweet walnut roll on a small plate beside a cup of coffee, and a worn, browned hand-written recipe card slightly out of focus behind it, nostalgic warm light.

**cooking-in-a-small-kitchen** — A simple comforting bowl of polenta topped with white cheese and a spoon of sour cream, on a narrow cluttered-but-cozy small-kitchen counter, one good knife and a wooden spoon nearby.

## Tips (hero images)

**store-cooked-food-safely** — Cooked food portioned into clean glass containers with lids on a bright kitchen counter, one open to show soup, a hand-written date label on a lid, tidy and practical.

**stock-a-practical-pantry** — An organized pantry shelf, labeled glass jars of rice, pasta, dried beans and lentils, tins of tomatoes, a few onions and garlic in a basket, soft even light.

**simple-dough-techniques** — Close-up of two floured hands kneading a smooth ball of dough on a well-worn wooden board, a light dusting of flour in the air, rolling pin to the side.

**choose-fresh-ingredients** — A market-style spread of fresh vegetables, herbs, eggs and a whole fish on ice arranged on a wooden counter, dewy and vivid but natural, a hand gently checking a tomato.

**cooking-with-the-seasons** — A flat-lay of seasonal produce grouped loosely by season (asparagus and peas; tomatoes and zucchini; squash and apples; root vegetables and cabbage) on a rustic table, natural light.

**home-pickling-basics** — Several glass jars of assorted homemade pickles (cucumbers with dill, sliced carrots, cauliflower, peppers) on a counter, brine and mustard seeds visible, one jar open with a fork.

---

## Optional: 1–2 process shots per recipe (strong authenticity signal)
Template: *"[hands / in-progress step], home kitchen, [specific action], "* + STYLE SUFFIX. Examples:
- cabbage-rolls: "Hands rolling a cabbage leaf around meat filling on a wooden board, a bowl of filling and a stack of blanched leaves beside."
- apple-pie: "Hands crimping the edge of a raw apple pie crust in a metal tin, flour on the counter."
- crepes-with-jam: "Pouring thin crêpe batter into a hot pan, swirling to coat, on a home stovetop."

## Author portrait — Isalune Merovik
Prefer a **real photo**. If generating: *"Natural candid portrait of a woman in her 30s–40s in a home kitchen, warm friendly expression, wearing a simple apron, soft window light, holding a cup of coffee or a wooden spoon, shallow depth of field, photojournalistic, no text."* (Square 1:1, ~800×800.) Note: a real, named, accountable person behind the byline is a much stronger E-E-A-T signal than any portrait.

## Brand assets
- **Wordmark (`kepoli-wordmark`)** — *"Minimal wordmark logo reading 'Kepoli' in a warm dark brown (#252416) serif, on transparent/cream background, clean, no icon."* (SVG/PNG, transparent.)
- **Icon / favicon (`kepoli-icon`)** — currently a simple "K" monogram (already generated). Optional upgrade: *"Simple rounded-square app icon, cream 'K' monogram or a tiny fork-and-spoon mark, on deep olive-brown (#2b2a1c), flat, no gradient."* (512×512 PNG.)
- **Social cover / OG (`kepoli-social-cover`)** — *"Warm banner image of a cozy home kitchen table with a few dishes and the mood of the site, generous empty space on one side for a title overlay."* (1200×630.)
