import fs from 'node:fs';

const posts = JSON.parse(fs.readFileSync('content/posts.json', 'utf8'));
const pages = JSON.parse(fs.readFileSync('content/pages.json', 'utf8'));
const categories = JSON.parse(fs.readFileSync('content/categories.json', 'utf8'));
const imagePlan = fs.existsSync('content/image-plan.json')
  ? JSON.parse(fs.readFileSync('content/image-plan.json', 'utf8'))
  : [];

const failures = [];
const slugs = new Set();
const categorySlugs = new Set(categories.map((category) => category.slug));
const imagePlanBySlug = new Map();

for (const post of posts) {
  if (slugs.has(post.slug)) failures.push(`Duplicate post slug: ${post.slug}`);
  slugs.add(post.slug);
  if (!categorySlugs.has(post.category)) failures.push(`Unknown category for ${post.slug}: ${post.category}`);
  if (!post.excerpt || post.excerpt.length < 70) failures.push(`Short excerpt: ${post.slug}`);
  if (!post.meta_description || post.meta_description.length < 70) failures.push(`Thin meta description: ${post.slug}`);

  if (post.kind === 'recipe') {
    for (const key of ['ingredients', 'steps', 'related', 'related_articles']) {
      if (!Array.isArray(post[key]) || post[key].length === 0) failures.push(`Missing ${key}: ${post.slug}`);
    }
    if ((post.ingredients || []).length < 6) failures.push(`Recipe needs more ingredients context: ${post.slug}`);
    if ((post.steps || []).length < 5) failures.push(`Recipe needs fuller preparation steps: ${post.slug}`);
    if (!post.notes || post.notes.length < 60) failures.push(`Recipe notes too short: ${post.slug}`);
    if ((post.related || []).length < 3) failures.push(`Recipe needs 3 related recipes: ${post.slug}`);
    if ((post.related_articles || []).length < 1) failures.push(`Recipe needs at least 1 related article: ${post.slug}`);
  }

  if (post.kind === 'article') {
    if (!Array.isArray(post.sections) || post.sections.length < 5) failures.push(`Article needs deeper sections: ${post.slug}`);
    if (!Array.isArray(post.takeaways) || post.takeaways.length < 4) failures.push(`Article needs takeaways: ${post.slug}`);
    if (!Array.isArray(post.faq) || post.faq.length < 3) failures.push(`Article needs FAQ: ${post.slug}`);
    for (const section of post.sections || []) {
      if (!section.heading || section.heading.length < 8) failures.push(`Weak article heading: ${post.slug}`);
      if (!section.body || section.body.length < 180) failures.push(`Thin article section: ${post.slug} / ${section.heading || 'section'}`);
    }
    for (const item of post.faq || []) {
      if (!item.question || !item.answer) failures.push(`Incomplete FAQ item: ${post.slug}`);
    }
    if (!Array.isArray(post.related) || post.related.length < 4) failures.push(`Article needs related recipes: ${post.slug}`);
    const articleWordCount = [
      post.excerpt,
      ...(post.takeaways || []),
      ...(post.sections || []).flatMap((section) => [section.heading, section.body]),
      ...(post.faq || []).flatMap((item) => [item.question, item.answer]),
    ]
      .join(' ')
      .split(/\s+/)
      .filter(Boolean).length;
    if (articleWordCount < 500) failures.push(`Article too thin overall: ${post.slug}`);
  }
}

for (const image of imagePlan) {
  if (!image || typeof image !== 'object') {
    failures.push('Invalid image plan item.');
    continue;
  }

  if (!image.slug || typeof image.slug !== 'string') {
    failures.push('Image plan item is missing slug.');
    continue;
  }

  if (imagePlanBySlug.has(image.slug)) failures.push(`Duplicate image plan slug: ${image.slug}`);
  imagePlanBySlug.set(image.slug, image);

  if (!slugs.has(image.slug)) failures.push(`Image plan refers to unknown post slug: ${image.slug}`);
  if (!image.filename || !/\.(jpg|jpeg|png|webp)$/i.test(image.filename)) failures.push(`Invalid image filename for ${image.slug}`);
  if (!image.alt || image.alt.length < 25) failures.push(`Image alt text too short: ${image.slug}`);
  if (!image.title || image.title.length < 8) failures.push(`Image title too short: ${image.slug}`);
  if (!image.caption || image.caption.length < 20) failures.push(`Image caption too short: ${image.slug}`);
  if (!image.description || image.description.length < 40) failures.push(`Image description too short: ${image.slug}`);
  if (!image.prompt || image.prompt.length < 120) failures.push(`Image prompt too short: ${image.slug}`);
}

for (const post of posts) {
  if (!imagePlanBySlug.has(post.slug)) failures.push(`Missing image plan entry: ${post.slug}`);
}

for (const post of posts) {
  for (const slug of [...(post.related || []), ...(post.related_articles || [])]) {
    if (!slugs.has(slug)) failures.push(`Broken relation from ${post.slug} to ${slug}`);
  }
}

const requiredPages = [
  'acasa',
  'retete',
  'articole',
  'despre-kepoli',
  'despre-autor',
  'contact',
  'politica-de-confidentialitate',
  'politica-de-cookies',
  'publicitate-si-consimtamant',
  'termeni-si-conditii',
  'disclaimer-culinar',
];
const pageSlugs = new Set(pages.map((page) => page.slug));
for (const slug of requiredPages) {
  if (!pageSlugs.has(slug)) failures.push(`Missing required page: ${slug}`);
}

for (const category of categories) {
  if (!category.description || category.description.length < 70) failures.push(`Category description too short: ${category.slug}`);
}

const recipeCount = posts.filter((post) => post.kind === 'recipe').length;
const articleCount = posts.filter((post) => post.kind === 'article').length;
if (posts.length !== 30) failures.push(`Expected 30 posts, found ${posts.length}`);
if (recipeCount !== 24) failures.push(`Expected 24 recipes, found ${recipeCount}`);
if (articleCount !== 6) failures.push(`Expected 6 articles, found ${articleCount}`);

if (failures.length) {
  console.error(failures.join('\n'));
  process.exit(1);
}

console.log(`Content OK: ${posts.length} posts (${recipeCount} recipes, ${articleCount} articles), ${pages.length} pages, ${categories.length} categories.`);
