import fs from 'node:fs';

const posts = JSON.parse(fs.readFileSync('content/posts.json', 'utf8'));
const pages = JSON.parse(fs.readFileSync('content/pages.json', 'utf8'));

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
]);

requireIncludes('publicitate-si-consimtamant', 'EEA consent disclosure', [
  /Google AdSense/i,
  /Romania/i,
  /consimtamant/i,
  /EEA|Spatiul Economic European/i,
]);

requireIncludes('politica-editoriala', 'editorial quality disclosures', [
  /Originalitate/i,
  /promisiuni exagerate/i,
  /sponsorizate|comerciale/i,
]);

requireIncludes('disclaimer-culinar', 'culinary disclaimer coverage', [
  /medic|nutritionist|dietetician/i,
  /Alergeni/i,
  /Siguranta alimentara/i,
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
