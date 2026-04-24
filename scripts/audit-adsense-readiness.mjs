import fs from 'node:fs';

const posts = JSON.parse(fs.readFileSync('content/posts.json', 'utf8'));
const pages = JSON.parse(fs.readFileSync('content/pages.json', 'utf8'));
const themeFiles = new Map([
  ['header', fs.readFileSync('wp-content/themes/kepoli/header.php', 'utf8')],
  ['functions', fs.readFileSync('wp-content/themes/kepoli/functions.php', 'utf8')],
  ['front-page', fs.readFileSync('wp-content/themes/kepoli/front-page.php', 'utf8')],
  ['single', fs.readFileSync('wp-content/themes/kepoli/single.php', 'utf8')],
  ['archive', fs.readFileSync('wp-content/themes/kepoli/archive.php', 'utf8')],
  ['search', fs.readFileSync('wp-content/themes/kepoli/search.php', 'utf8')],
  ['page', fs.readFileSync('wp-content/themes/kepoli/page.php', 'utf8')],
  ['page-retete', fs.readFileSync('wp-content/themes/kepoli/page-retete.php', 'utf8')],
  ['page-articole', fs.readFileSync('wp-content/themes/kepoli/page-articole.php', 'utf8')],
  ['template-parts-card', fs.readFileSync('wp-content/themes/kepoli/template-parts-card.php', 'utf8')],
  ['template-parts-sidebar', fs.readFileSync('wp-content/themes/kepoli/template-parts-sidebar.php', 'utf8')],
]);
const seedBootstrap = fs.readFileSync('seed/bootstrap.php', 'utf8');

const failures = [];
const notes = [];
const softNotes = [];

const pageBySlug = new Map(pages.map((page) => [page.slug, page]));

function requirePage(slug) {
  const page = pageBySlug.get(slug);
  if (!page) {
    failures.push(`Missing page: ${slug}`);
    return '';
  }

  return String(page.content || '');
}

function requireIncludes(slug, label, patterns) {
  const content = requirePage(slug);
  if (!content) return;

  for (const pattern of patterns) {
    if (!pattern.test(content)) {
      failures.push(`Page ${slug} is missing ${label}: ${pattern}`);
    }
  }
}

function requireThemeIncludes(fileKey, label, patterns) {
  const content = themeFiles.get(fileKey);
  if (!content) {
    failures.push(`Missing theme file for audit: ${fileKey}`);
    return;
  }

  for (const pattern of patterns) {
    if (!pattern.test(content)) {
      failures.push(`Theme file ${fileKey} is missing ${label}: ${pattern}`);
    }
  }
}

function requireSeedIncludes(label, patterns) {
  for (const pattern of patterns) {
    if (!pattern.test(seedBootstrap)) {
      failures.push(`Seed bootstrap is missing ${label}: ${pattern}`);
    }
  }
}

function wordCount(value) {
  return String(value || '')
    .replace(/<[^>]+>/g, ' ')
    .split(/\s+/)
    .filter(Boolean).length;
}

requireIncludes('despre-kepoli', 'trust/originality language', [
  /publicitate/i,
  /politica-editoriala/i,
  /Nu republicam integral materiale/i,
]);

requireIncludes('despre-autor', 'editorial accountability', [
  /feedback/i,
  /promisiuni exagerate/i,
  /politica-editoriala/i,
]);

requireIncludes('contact', 'direct contact details', [
  /mailto:/i,
  /linkul paginii|browser|titlul exact/i,
]);

requireIncludes('politica-de-confidentialitate', 'Google advertising disclosure', [
  /Google AdSense/i,
  /Ads Settings|adssettings/i,
  /aboutads/i,
  /Romania/i,
]);

requireIncludes('politica-de-cookies', 'cookie consent disclosure', [
  /Cookie-uri publicitare/i,
  /consimtamant/i,
  /Google/i,
  /continutul editorial/i,
]);

requireIncludes('publicitate-si-consimtamant', 'EEA consent disclosure', [
  /Google AdSense/i,
  /Romania/i,
  /consimtamant/i,
  /EEA|Spatiul Economic European/i,
  /nepersonalizate/i,
  /continutul editorial/i,
]);

requireIncludes('politica-editoriala', 'editorial quality disclosures', [
  /Originalitate/i,
  /promisiuni exagerate/i,
  /sponsorizate|comerciale/i,
  /subiecte/i,
  /Titluri|titlurile/i,
]);

requireIncludes('disclaimer-culinar', 'culinary disclaimer coverage', [
  /medic|nutritionist|dietetician/i,
  /Alergeni/i,
  /Siguranta alimentara/i,
]);

requireThemeIncludes('header', 'editorial utility links', [
  /kepoli_author_page_url\s*\(/,
  /home_url\('\/contact\/'\)/,
]);

requireThemeIncludes('functions', 'card meta helpers', [
  /function kepoli_post_card_meta_items\s*\(/,
  /function kepoli_render_post_card_meta\s*\(/,
  /function kepoli_category_card_image_data\s*\(/,
]);

requireThemeIncludes('front-page', 'homepage trust links', [
  /kepoli_render_reader_trust_links\s*\(/,
  /kepoli_render_post_card_meta\s*\(/,
  /kepoli_category_card_image_data\s*\(/,
  /category-card__visual/,
]);

for (const fileKey of ['single', 'archive', 'search', 'page', 'page-retete', 'page-articole']) {
  requireThemeIncludes(fileKey, 'reader trust links', [
    /kepoli_render_reader_trust_links\s*\(/,
  ]);
}

requireThemeIncludes('page-retete', 'category card visual proof', [
  /kepoli_category_card_image_data\s*\(/,
  /category-card__visual/,
]);

for (const fileKey of ['template-parts-card', 'single', 'page-retete', 'page-articole']) {
  requireThemeIncludes(fileKey, 'editorial card metadata', [
    /kepoli_render_post_card_meta\s*\(/,
  ]);
}

requireThemeIncludes('template-parts-sidebar', 'sidebar metadata helper', [
  /kepoli_post_card_meta_items\s*\(/,
]);

requireThemeIncludes('page', 'page trust navigation', [
  /kepoli_page_resource_links\s*\(/,
]);

requireThemeIncludes('archive', 'archive guidance support', [
  /kepoli_archive_guidance_items\s*\(/,
  /archive-guide/,
]);

requireThemeIncludes('single', 'recipe snapshot support', [
  /kepoli_recipe_snapshot_items\s*\(/,
  /entry-recipe-snapshot/,
]);

requireThemeIncludes('single', 'early featured image support', [
  /entry-featured-media--header/,
  /entry-featured-media--header[\s\S]*entry-summary/,
]);

requireSeedIncludes('distinct intro support', [
  /function kepoli_seed_post_intro\s*\(/,
  /kepoli_seed_post_intro\(\$post\)/,
]);

const riskyClaims = [
  /\bdetox\b/i,
  /\bmiracol(?:oasa|os|ul)?\b/i,
  /\btrateaza\b/i,
  /\bvindeca\b/i,
  /\bslabesti\b/i,
  /\bslabire\b/i,
  /\bslabit\b/i,
  /\bpierdere in greutate\b/i,
  /\bgarantat(?:a|e)?\b/i,
  /\bfara efort\b/i,
  /\bantiinflamator\b/i,
];

for (const post of posts) {
  const haystack = JSON.stringify(post);
  for (const pattern of riskyClaims) {
    if (pattern.test(haystack)) {
      failures.push(`Risky claim language in post ${post.slug}: ${pattern}`);
    }
  }

  const totalWords = wordCount(
    [
      post.excerpt,
      post.notes,
      ...(post.ingredients || []),
      ...(post.steps || []),
      ...(post.takeaways || []),
      ...(post.sections || []).flatMap((section) => [section.heading, section.body]),
      ...(post.faq || []).flatMap((item) => [item.question, item.answer]),
    ].join(' ')
  );

  if (post.kind === 'recipe' && totalWords < 75) {
    failures.push(`Recipe source data is too thin: ${post.slug} (${totalWords} words in source data)`);
  }

  if (post.kind === 'article' && totalWords < 500) {
    failures.push(`Article source data is too thin: ${post.slug} (${totalWords} words in source data)`);
  }

  if (post.kind === 'recipe' && totalWords < 120) {
    softNotes.push(`Recipe source data is compact but acceptable because seed rendering expands it: ${post.slug} (${totalWords} words)`);
  }

  if (post.kind === 'article' && totalWords < 650) {
    softNotes.push(`Article source data is on the lighter side: ${post.slug} (${totalWords} words)`);
  }
}

if (posts.length < 30) {
  failures.push(`Only ${posts.length} posts found.`);
}

if (pages.length < 12) {
  failures.push(`Only ${pages.length} pages found.`);
}

notes.push(`Posts: ${posts.length}`);
notes.push(`Pages: ${pages.length}`);
notes.push(`Recipes: ${posts.filter((post) => post.kind === 'recipe').length}`);
notes.push(`Articles: ${posts.filter((post) => post.kind === 'article').length}`);

if (failures.length) {
  console.error(failures.join('\n'));
  process.exit(1);
}

console.log('AdSense content audit OK.');
for (const note of notes) {
  console.log(`- ${note}`);
}
for (const note of softNotes) {
  console.log(`- Note: ${note}`);
}
