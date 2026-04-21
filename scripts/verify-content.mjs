import fs from 'node:fs';

const posts = JSON.parse(fs.readFileSync('content/posts.json', 'utf8'));
const pages = JSON.parse(fs.readFileSync('content/pages.json', 'utf8'));
const categories = JSON.parse(fs.readFileSync('content/categories.json', 'utf8'));

const failures = [];
const slugs = new Set();
const categorySlugs = new Set(categories.map((category) => category.slug));

for (const post of posts) {
  if (slugs.has(post.slug)) failures.push(`Duplicate post slug: ${post.slug}`);
  slugs.add(post.slug);
  if (!categorySlugs.has(post.category)) failures.push(`Unknown category for ${post.slug}: ${post.category}`);
  if (!post.excerpt || post.excerpt.length < 60) failures.push(`Short excerpt: ${post.slug}`);

  if (post.kind === 'recipe') {
    for (const key of ['ingredients', 'steps', 'related', 'related_articles']) {
      if (!Array.isArray(post[key]) || post[key].length === 0) failures.push(`Missing ${key}: ${post.slug}`);
    }
    if ((post.related || []).length < 3) failures.push(`Recipe needs 3 related recipes: ${post.slug}`);
  }

  if (post.kind === 'article') {
    if (!Array.isArray(post.sections) || post.sections.length < 3) failures.push(`Article needs sections: ${post.slug}`);
    if (!Array.isArray(post.related) || post.related.length < 4) failures.push(`Article needs related recipes: ${post.slug}`);
  }
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
