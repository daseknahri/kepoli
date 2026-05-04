# AI Content Growth Strategy

This document captures the next strategic direction for the food-blog engine and the two live sites.

The goal is to use AI as an editorial and optimization system, not as uncontrolled auto-publishing. The sites should become easier to operate, better at turning outside AI drafts into clean posts, and more intentional about traffic, monetization, and long-term trust.

## Site Goals

### Kepoli

Kepoli is the Romanian, AdSense-first site.

Primary goals:
- Keep the site clean, trustworthy, and AdSense-safe.
- Publish Romanian recipes and helpful kitchen articles with strong originality and clear public value.
- Build long-term SEO and brand trust.
- Avoid aggressive ads, misleading claims, thin AI content, and risky health-style articles.

Best content types:
- Romanian family recipes.
- Traditional food with practical modern versions.
- Budget meals and everyday cooking.
- Ingredient guides.
- Storage, meal planning, and kitchen mistake articles.
- Soft health-adjacent food articles only when they are careful, non-medical, and useful.

### KuchniaTwist

KuchniaTwist is the English, Facebook-first monetization test site.

Primary goals:
- Build content that Facebook readers over 40 want to open and continue reading.
- Increase finalized revenue per 1,000 Facebook clicks.
- Use monetization more aggressively than Kepoli, but keep it controlled enough to avoid destroying user trust, Facebook reach, or ad-network payment quality.
- Treat SEO as a secondary benefit, not the first growth channel.

Best content types:
- Problem-solution cooking articles.
- Nostalgic recipes and comfort food.
- Mistake/fix articles.
- Budget and pantry cooking.
- Simple recipes with emotional or practical hooks.
- Short guides that create curiosity and deliver useful payoff.

## Core Principle

AI can help write faster than a human, but the system should still behave like an editor.

Good AI use:
- Extract structure from pasted posts.
- Improve SEO metadata.
- Suggest tags, categories, internal links, and image alt text.
- Detect weak titles, thin introductions, bad schema, repeated tags, and missing post sections.
- Generate multiple Facebook hooks from the same article.
- Score whether a post is AdSense-safe or Facebook-friendly.

Bad AI use:
- Auto-publishing unreviewed posts.
- Generating large volumes of shallow articles.
- Making unsupported medical claims.
- Writing fake urgency or misleading clickbait.
- Creating content only to force ad impressions.

## Open Questions

### AI Model Choice

Questions:
- Should the plugin call OpenRouter directly, or should the outside writing tool remain separate?
- Are free OpenRouter models reliable enough for production workflow?
- Should we use one model for extraction and another model for editorial scoring?

Possible paths:
- Start with AI disabled by default and add an optional admin-only analysis button.
- Use deterministic parsing first, AI second, and validation last.
- Use free OpenRouter models for non-critical suggestions only.
- Keep paid or stronger models optional for higher-quality editorial review.

Useful tools:
- OpenRouter API.
- ChatGPT or other outside AI writers.
- WordPress admin plugin UI.
- JSON schema validation inside the plugin.
- Existing content verification scripts.

### Plugin Extraction And SEO Enhancement

Questions:
- Which fields should AI be allowed to fill automatically?
- Which fields should always require human review?
- Should the plugin rewrite content, or only extract and suggest?

Possible paths:
- Add `AI Analyze` in the post editor.
- Return strict JSON only.
- Let AI suggest recipe schema, excerpt, meta title, meta description, image metadata, tags, and category.
- Reject invalid or overly long fields before saving.
- Keep the current deterministic recipe/article parser as the fallback.

Useful tools:
- OpenRouter free models for extraction drafts.
- Existing WordPress plugin parser.
- JavaScript admin UI.
- PHP sanitization and validation.
- `scripts/verify-content.mjs`.

### Content Planning

Questions:
- How many posts should each site publish per week?
- Which niches produce the highest Facebook click-through without becoming clickbait?
- Which content should be evergreen for SEO versus short-term Facebook traffic?

Possible paths:
- Kepoli publishes fewer, cleaner posts focused on trust and AdSense.
- KuchniaTwist publishes faster and tests headline angles with Facebook traffic.
- Build a monthly content calendar with recipes, articles, and Facebook caption variants.
- Keep a small performance log of title, topic, traffic source, clicks, revenue, and reader behavior.

Useful tools:
- Google Trends.
- Facebook page insights.
- Histats.
- Google Search Console.
- Manual competitor review.
- A monthly `content-plan.md` file.

### Facebook Traffic Strategy

Questions:
- Which hooks work best for an audience over 40?
- How aggressive can monetization be before reach drops?
- Should prelanders be used often or only for tests?

Possible paths:
- Use warm curiosity instead of hard clickbait.
- Generate three Facebook captions per post: practical, emotional, and curiosity-based.
- Track every Facebook link with UTM parameters.
- Use KuchniaTwist split posts only when the split improves reading flow.
- Increase ads only after the user has shown intent, such as scrolling, clicking next, or continuing to another part.

Useful tools:
- Facebook page analytics.
- UTM links.
- Histats traffic-by-URL.
- Optional GA4 later.
- Existing ad mode environment variables.

### Monetization Strategy

Questions:
- Which ad combinations maximize revenue without making the site feel spammy?
- Should KuchniaTwist test more aggressive formats only on engaged users?
- When should Kepoli add AdSense units after approval?

Possible paths:
- Keep Kepoli clean until AdSense is approved and stable.
- Keep KuchniaTwist as the ad test site.
- Test one ad change at a time.
- Measure finalized revenue per 1,000 Facebook clicks, not just dashboard RPM.
- Keep aggressive ads behind time, scroll, or click-intent gates.

Useful tools:
- AdSense for Kepoli.
- Adsterra and Monetag for KuchniaTwist.
- Histats.
- Coolify environment variables.
- Ad operations docs.

### Analytics

Questions:
- Is Histats enough for the first traffic phase?
- When should GA4 or another analytics layer be added?
- Which events matter most for revenue decisions?

Possible paths:
- Use Histats first because it is simple and fast.
- Add GA4 later if deeper event tracking becomes necessary.
- Track post URL, traffic source, device, pageviews/session, time on site, and revenue.
- For KuchniaTwist, add events for split navigation, related post clicks, scroll depth, and ad-trigger actions.

Useful tools:
- Histats.
- GA4.
- Google Search Console.
- Facebook insights.
- Monetag and Adsterra dashboards.

## Practical Roadmap

### Phase 1: Manual AI Workflow

Do this first.

- Use outside AI to generate only title and clean content.
- Paste manually into WordPress.
- Use the plugin to extract recipe/article fields.
- Use Histats and ad dashboards to observe behavior.
- Build a simple spreadsheet or markdown log of post performance.

### Phase 2: AI-Assisted Plugin Analysis

Do this after the manual workflow is stable.

- Add optional OpenRouter support.
- Add an admin-only `AI Analyze` button.
- Generate strict JSON suggestions for metadata, schema, tags, categories, and Facebook hooks.
- Do not auto-publish.
- Keep all generated fields editable.

### Phase 3: Content Calendar System

Do this once the sites have enough posts to compare.

- Create a 30-day calendar for each site.
- Kepoli focuses on AdSense-safe trust and SEO.
- KuchniaTwist focuses on Facebook reader curiosity and monetized continuation.
- Review performance weekly.
- Repeat winning topics and remove weak formats.

### Phase 4: Optimization Layer

Do this after real traffic data exists.

- Score posts before publishing.
- Suggest better titles and split points.
- Flag thin content, repeated tags, missing alt text, bad schema, and weak excerpts.
- Compare content types against revenue per 1,000 clicks.
- Adjust ad strategy based on real finalized revenue.

## Default 30-Day Content Plan

### Kepoli

Target: 3-4 posts per week.

Suggested mix:
- 8 Romanian recipes.
- 4 practical kitchen guides.
- 2 ingredient or storage articles.
- 2 budget meal articles.

Publishing rule:
- Quality over speed.
- Keep titles natural.
- Keep public tone Romanian and trustworthy.
- Avoid aggressive monetization until AdSense is approved and stable.

### KuchniaTwist

Target: 1 post per day.

Suggested mix:
- 15 recipes.
- 8 problem/fix cooking articles.
- 4 nostalgia or comfort-food articles.
- 3 budget/pantry articles.

Publishing rule:
- Every post should have a clear Facebook angle.
- Every post should give a real payoff after the hook.
- Use split posts only when the second part contains meaningful content.
- Test ad changes slowly and document them.

## Universal Outside-AI Prompt Direction

Use outside AI for drafting, but keep the output simple for the plugin.

Required output:
- Title.
- Content only.
- No HTML.
- No markdown tables.
- No fake sources.
- No exaggerated medical claims.
- Clear sections.
- Natural human tone.
- Useful details and practical payoff.

For recipes:
- Include servings, prep time, cook/rest time, total time, difficulty, ingredients, method, serving ideas, tips, variations, storage, FAQ, and conclusion.

For articles:
- Include a strong introduction, practical sections, examples, common mistakes, clear takeaways, and conclusion.

## Success Metrics

Kepoli:
- AdSense approval.
- Clean policy pages.
- Stable indexing.
- Growing search impressions.
- Low SEO errors.
- High trust and low policy risk.

KuchniaTwist:
- Revenue per 1,000 Facebook clicks.
- Facebook reach stability.
- Mobile engagement.
- Pages per session.
- Split-post continuation rate.
- Finalized revenue versus dashboard revenue.
- Low complaint rate.

## Future Build Candidates

- `AI Analyze` plugin button.
- AI JSON schema validator.
- Facebook hook generator.
- Post quality score.
- AdSense safety score.
- Viral/Facebook readability score.
- Monthly content-plan generator.
- Performance log template.
- UTM link helper.
- Split-post quality checker.
