import fs from 'node:fs';

const posts = JSON.parse(fs.readFileSync('content/posts.json', 'utf8'));
const pages = JSON.parse(fs.readFileSync('content/pages.json', 'utf8'));
const envExample = fs.readFileSync('.env.example', 'utf8');
const readme = fs.readFileSync('README.md', 'utf8');
const adsenseDocs = fs.readFileSync('docs/adsense-readiness.md', 'utf8');
const dockerCompose = fs.readFileSync('docker-compose.yml', 'utf8');
const wordpressDockerfile = fs.readFileSync('docker/wordpress/Dockerfile', 'utf8');
const apachePerformanceConf = fs.readFileSync('docker/wordpress/kepoli-performance.conf', 'utf8');
const adtechMuPlugin = fs.readFileSync('wp-content/mu-plugins/kepoli-adtech.php', 'utf8');
const newsletterMuPlugin = fs.readFileSync('wp-content/mu-plugins/kepoli-newsletter.php', 'utf8');
const siteJs = fs.readFileSync('wp-content/themes/kepoli/assets/js/site.js', 'utf8');
const siteMinJs = fs.readFileSync('wp-content/themes/kepoli/assets/js/site.min.js', 'utf8');
const articleJs = fs.readFileSync('wp-content/themes/kepoli/assets/js/article.js', 'utf8');
const articleMinJs = fs.readFileSync('wp-content/themes/kepoli/assets/js/article.min.js', 'utf8');
const themeFiles = new Map([
  ['header', fs.readFileSync('wp-content/themes/kepoli/header.php', 'utf8')],
  ['footer', fs.readFileSync('wp-content/themes/kepoli/footer.php', 'utf8')],
  ['functions', fs.readFileSync('wp-content/themes/kepoli/functions.php', 'utf8')],
  ['front-page', fs.readFileSync('wp-content/themes/kepoli/front-page.php', 'utf8')],
  ['single', fs.readFileSync('wp-content/themes/kepoli/single.php', 'utf8')],
  ['archive', fs.readFileSync('wp-content/themes/kepoli/archive.php', 'utf8')],
  ['search', fs.readFileSync('wp-content/themes/kepoli/search.php', 'utf8')],
  ['page', fs.readFileSync('wp-content/themes/kepoli/page.php', 'utf8')],
  ['page-despre-kepoli', fs.readFileSync('wp-content/themes/kepoli/page-despre-kepoli.php', 'utf8')],
  ['page-retete', fs.readFileSync('wp-content/themes/kepoli/page-retete.php', 'utf8')],
  ['page-articole', fs.readFileSync('wp-content/themes/kepoli/page-articole.php', 'utf8')],
  ['page-despre-autor', fs.readFileSync('wp-content/themes/kepoli/page-despre-autor.php', 'utf8')],
  ['template-parts-card', fs.readFileSync('wp-content/themes/kepoli/template-parts-card.php', 'utf8')],
  ['template-parts-sidebar', fs.readFileSync('wp-content/themes/kepoli/template-parts-sidebar.php', 'utf8')],
]);
const seedBootstrap = fs.readFileSync('seed/bootstrap.php', 'utf8');
const writerPhotoSvg = fs.readFileSync('wp-content/themes/kepoli/assets/img/writer-photo.svg', 'utf8');
const obsoleteThemePngs = [
  'wp-content/themes/kepoli/assets/img/hero-homepage.png',
  'wp-content/themes/kepoli/assets/img/kepoli-social-cover.png',
  'wp-content/themes/kepoli/assets/img/writer-photo.png',
];
const themeAssetStats = {
  heroJpg: fs.statSync('wp-content/themes/kepoli/assets/img/hero-homepage.jpg').size,
  socialCoverJpg: fs.statSync('wp-content/themes/kepoli/assets/img/kepoli-social-cover.jpg').size,
  writerJpg: fs.statSync('wp-content/themes/kepoli/assets/img/writer-photo.jpg').size,
  styleCss: fs.statSync('wp-content/themes/kepoli/style.css').size,
  styleMinCss: fs.statSync('wp-content/themes/kepoli/style.min.css').size,
  siteJs: fs.statSync('wp-content/themes/kepoli/assets/js/site.js').size,
  siteMinJs: fs.statSync('wp-content/themes/kepoli/assets/js/site.min.js').size,
  articleJs: fs.statSync('wp-content/themes/kepoli/assets/js/article.js').size,
  articleMinJs: fs.statSync('wp-content/themes/kepoli/assets/js/article.min.js').size,
};

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

function requireTextIncludes(label, value, patterns) {
  for (const pattern of patterns) {
    if (!pattern.test(value)) {
      failures.push(`${label} is missing: ${pattern}`);
    }
  }
}

function wordCount(value) {
  return String(value || '')
    .replace(/<[^>]+>/g, ' ')
    .split(/\s+/)
    .filter(Boolean).length;
}

function rejectPublicCopy(label, value, patterns) {
  for (const pattern of patterns) {
    if (pattern.test(String(value || ''))) {
      failures.push(`Production copy issue in ${label}: ${pattern}`);
    }
  }
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

for (const page of pages) {
  rejectPublicCopy(`page ${page.slug}`, page.content, [
    /placeholder/i,
    /lorem/i,
    /dummy/i,
    /functionare si dezvoltare/i,
  ]);
}

for (const post of posts) {
  rejectPublicCopy(`post ${post.slug}`, JSON.stringify(post), [
    /placeholder/i,
    /lorem/i,
    /dummy/i,
  ]);
}

rejectPublicCopy('sidebar author box', themeFiles.get('template-parts-sidebar'), [
  /Retete romanesti testate/i,
]);

rejectPublicCopy('theme public Romanian copy', [...themeFiles.values()].join('\n'), [
  /['"]%d min read['"]/i,
  /['"]Kepoli home['"]/,
  /['"]Breadcrumbs['"]/,
]);

rejectPublicCopy('author illustration asset', writerPhotoSvg, [
  /placeholder/i,
]);

requireThemeIncludes('functions', 'deploy fingerprint opt-in guard', [
  /function kepoli_deploy_fingerprint_meta\(\): void[\s\S]*KEPOLI_DEPLOY_FINGERPRINT/,
]);

requireTextIncludes('.env.example Google service gates', envExample, [
  /ADSENSE_ENABLE=0/,
  /GA_ENABLE=0/,
  /GA_MEASUREMENT_ID=/,
  /CANONICAL_REDIRECT_HOSTS=www\.kepoli\.com,api\.kepoli\.com,recipe\.kepoli\.com/,
]);

requireTextIncludes('docker compose Google service gates', dockerCompose, [
  /GA_ENABLE:\s*\$\{GA_ENABLE:-0\}/,
  /ADSENSE_ENABLE:\s*\$\{ADSENSE_ENABLE:-0\}/,
  /CANONICAL_REDIRECT_HOSTS:\s*\$\{CANONICAL_REDIRECT_HOSTS:-www\.kepoli\.com,api\.kepoli\.com,recipe\.kepoli\.com\}/,
]);

requireTextIncludes('canonical host redirects', adtechMuPlugin, [
  /function kepoli_mu_redirect_hosts\(string \$canonical_host\): array/,
  /CANONICAL_REDIRECT_HOSTS/,
  /www\.' \. \$canonical_host/,
  /api\.' \. \$canonical_host/,
  /recipe\.' \. \$canonical_host/,
  /wp_redirect\(\$scheme \. ':\/\/' \. \$canonical_host \. \$request_uri,\s*301,\s*'Kepoli'\)/,
]);

requireTextIncludes('machine-readable trust files', adtechMuPlugin, [
  /\/ads\.txt/,
  /\/\.well-known\/security\.txt/,
  /Contact:\s*mailto:/,
  /Preferred-Languages:\s*ro,\s*en/,
  /Canonical:/,
]);

requireTextIncludes('production Apache performance config', `${wordpressDockerfile}\n${apachePerformanceConf}`, [
  /a2enmod headers expires deflate/,
  /a2enconf kepoli-performance/,
  /Cache-Control "public, max-age=31536000, immutable"/,
  /AddOutputFilterByType DEFLATE/,
  /ExpiresByType text\/css "access plus 1 year"/,
]);

requireTextIncludes('AdSense docs consent gates', `${readme}\n${adsenseDocs}`, [
  /ADSENSE_ENABLE=0[^.\n]*GA_ENABLE=0|GA_ENABLE=0[^.\n]*ADSENSE_ENABLE=0/,
  /consent/i,
]);

requireThemeIncludes('functions', 'frontend output cleanup', [
  /function kepoli_trim_wordpress_frontend_output\(\): void/,
  /remove_action\('wp_head',\s*'print_emoji_detection_script'/,
  /remove_action\('wp_head',\s*'wp_generator'/,
  /remove_action\('wp_head',\s*'wp_oembed_add_discovery_links'/,
  /function kepoli_dequeue_unused_frontend_assets\(\): void/,
  /wp_dequeue_style\('wp-block-library'\)/,
]);

requireThemeIncludes('functions', 'robots indexing policy', [
  /function kepoli_robots_content\(\): string/,
  /is_search\(\) \|\| is_404\(\)/,
  /noindex,follow,max-image-preview:large/,
  /index,follow,max-image-preview:large/,
  /esc_attr\(kepoli_robots_content\(\)\)/,
]);

requireThemeIncludes('functions', 'priority image preloads', [
  /function kepoli_priority_image_preloads\(\): void/,
  /rel=\\"preload\\" as=\\"image\\"/,
  /fetchpriority=\\"high\\"/,
  /imagesrcset/,
  /hero-homepage',\s*'jpg'/,
]);

requireThemeIncludes('functions', 'theme image dimensions', [
  /function kepoli_asset_dimensions\(string \$basename\): array/,
  /function kepoli_dimension_attributes\(array \$item\): string/,
  /function kepoli_asset_dimension_attributes\(string \$basename\): string/,
  /wp_get_attachment_image_src\(\$thumbnail_id,\s*\$cover_size\)/,
  /'width'\s*=>\s*is_array\(\$image\)/,
  /'height'\s*=>\s*is_array\(\$image\)/,
]);

requireThemeIncludes('header', 'wordmark dimensions', [
  /kepoli_asset_dimension_attributes\('kepoli-wordmark'\)/,
]);

requireThemeIncludes('front-page', 'static image dimensions', [
  /kepoli_asset_dimension_attributes\('hero-homepage'\)/,
  /kepoli_asset_dimension_attributes\('writer-photo'\)/,
  /kepoli_dimension_attributes\(\$category_image\)/,
  /kepoli_dimension_attributes\(\$gallery_item\)/,
]);

requireThemeIncludes('page-retete', 'category image dimensions', [
  /kepoli_dimension_attributes\(\$category_image\)/,
  /kepoli_dimension_attributes\(\$gallery_item\)/,
]);

requireThemeIncludes('template-parts-sidebar', 'author image dimensions', [
  /writer-photo',\s*'jpg'/,
  /kepoli_asset_dimension_attributes\('writer-photo'\)/,
]);

requireThemeIncludes('functions', 'conditional Google resource hints', [
  /function kepoli_resource_hints\(array \$urls,\s*string \$relation_type\): array/,
  /kepoli_ga_enabled\(\)\s*&&\s*kepoli_env\('GA_MEASUREMENT_ID'\)/,
  /kepoli_ads_enabled\(\)\s*&&\s*kepoli_env\('ADSENSE_CLIENT_ID'\)/,
]);

requireThemeIncludes('functions', 'Analytics consent gate', [
  /function kepoli_ga_enabled\(\): bool/,
  /GA_ENABLE/,
  /if \(\$measurement_id === '' \|\| !kepoli_ga_enabled\(\)\)/,
]);

requireThemeIncludes('functions', 'inline newsletter CTA markup', [
  /function kepoli_newsletter_cta\(string \$class = ''\): string/,
  /newsletter-cta/,
  /admin-post\.php/,
  /kepoli_newsletter_signup/,
  /newsletter_email/,
  /newsletter-cta__form/,
  /newsletter-cta__input/,
  /kepoli_newsletter_nonce/,
]);

requireThemeIncludes('front-page', 'homepage inline newsletter placement', [
  /kepoli_newsletter_cta\('newsletter-cta--compact newsletter-cta--homepage'\)/,
]);

requireThemeIncludes('page-despre-kepoli', 'about page inline newsletter placement', [
  /kepoli_newsletter_cta\('newsletter-cta--compact newsletter-cta--about'\)/,
]);

rejectPublicCopy('single post newsletter placement', themeFiles.get('single'), [
  /kepoli_newsletter_cta\(/,
]);

requireTextIncludes('compact newsletter styling', themeFiles.get('style') ?? fs.readFileSync('wp-content/themes/kepoli/style.css', 'utf8'), [
  /\.newsletter-cta\s*\{/,
  /width:\s*min\(100%,\s*520px\)/,
  /\.newsletter-cta__form\s*\{/,
  /\.newsletter-cta__input\s*\{/,
  /\.newsletter-cta__notice--success/,
]);

rejectPublicCopy('theme Reader Revenue popup initialization', [...themeFiles.values()].join('\n'), [
  /swg-basic\.js/,
  /news\.google\.com/,
  /basicSubscriptions\.init/,
  /isPartOfProductId/,
  /type:\s*['"]NewsArticle['"]/,
  /rrm-inline-cta/,
  /RRM_NEWSLETTER_CTA_ID/,
]);

requireTextIncludes('newsletter storage MU plugin', newsletterMuPlugin, [
  /Plugin Name:\s*Kepoli Newsletter Signups/,
  /register_post_type\(kepoli_newsletter_post_type\(\)/,
  /'show_ui'\s*=>\s*true/,
  /'menu_icon'\s*=>\s*'dashicons-email-alt'/,
  /Export CSV/,
  /remove_meta_box\('submitdiv'/,
  /Detalii abonare/,
  /mailto:/,
  /admin_post_nopriv_kepoli_newsletter_signup/,
  /admin_post_kepoli_newsletter_signup/,
  /admin_action_kepoli_export_newsletter/,
  /check_admin_referer\('kepoli_export_newsletter'\)/,
  /_kepoli_newsletter_email/,
  /_kepoli_newsletter_source_label/,
]);

requireThemeIncludes('functions', 'structured data image and entity details', [
  /function kepoli_schema_image_object\(string \$url,\s*array \$dimensions = \[\],\s*string \$caption = ''\): array/,
  /function kepoli_social_image_schema_object\(\): array/,
  /function kepoli_schema_publisher\(\): array/,
  /function kepoli_recipe_step_anchor\(int \$position\): string/,
  /function kepoli_recipe_step_name\(string \$step\): string/,
  /function kepoli_recipe_keywords\(int \$post_id = 0\): string/,
  /'@id'\s*=>\s*home_url\('\/#organization'\)/,
  /'image'\s*=>\s*\[kepoli_social_image_schema_object\(\)\]/,
  /'mainEntityOfPage'\s*=>\s*\[\s*'@type'\s*=>\s*'WebPage'/s,
  /'inLanguage'\s*=>\s*get_bloginfo\('language'\)/,
  /'dateModified'\s*=>\s*get_the_modified_date\('c'\)/,
  /'recipeInstructions'\s*=>\s*array_map\(/,
  /'name'\s*=>\s*kepoli_recipe_step_name/,
  /'url'\s*=>\s*get_permalink\(\) \. '#' \. kepoli_recipe_step_anchor/,
  /'image'\s*=>\s*\$recipe_image/,
  /\$schema\['keywords'\]\s*=\s*\$keywords/,
  /kepoli_schema_asset_image_object\('writer-photo',\s*'jpg',\s*'Isalune Merovik'\)/,
]);

requireTextIncludes('seed recipe step anchors', seedBootstrap, [
  /<li id="mod-de-preparare-step-/,
]);

requireThemeIncludes('functions', 'production stylesheet enqueue', [
  /style\.min\.css/,
  /filemtime\(\$style_path\)/,
]);

requireThemeIncludes('functions', 'production script enqueue', [
  /site\.min\.js/,
  /filemtime\(\$global_script\)/,
  /wp_script_add_data\('kepoli-site',\s*'strategy',\s*'defer'\)/,
  /article\.min\.js/,
  /filemtime\(\$article_script\)/,
  /wp_script_add_data\('kepoli-article',\s*'strategy',\s*'defer'\)/,
]);

requireTextIncludes('throttled frontend article script', `${articleJs}\n${articleMinJs}`, [
  /requestAnimationFrame\(updateProgress\)/,
  /scheduleProgressUpdate/,
]);

if (
  /console\.(log|warn|error|debug)/.test(siteJs) ||
  /console\.(log|warn|error|debug)/.test(siteMinJs) ||
  /console\.(log|warn|error|debug)/.test(articleJs) ||
  /console\.(log|warn|error|debug)/.test(articleMinJs)
) {
  failures.push('Production frontend scripts should not write to the browser console.');
}

if (themeAssetStats.siteMinJs >= themeAssetStats.siteJs) {
  failures.push('Minified frontend script is not smaller than site.js.');
}

if (themeAssetStats.articleMinJs >= themeAssetStats.articleJs) {
  failures.push('Minified article script is not smaller than article.js.');
}

requireThemeIncludes('functions', 'responsive lazy post media images', [
  /function kepoli_post_media_image_attrs/,
  /wp_get_attachment_image\(\$image_id,\s*\$size,\s*false,\s*\$attr\)/,
  /'loading'\s*=>\s*\$priority\s*\?\s*'eager'\s*:\s*'lazy'|'loading'\s*=>\s*'lazy'/,
  /'decoding'\s*=>\s*'async'/,
  /'sizes'\s*=>\s*\$sizes/,
]);

requireThemeIncludes('front-page', 'priority homepage hero image', [
  /class="home-hero__image"/,
  /fetchpriority="high"/,
  /loading="eager"/,
]);

requireThemeIncludes('functions', 'footer legal fallback items', [
  /function kepoli_footer_menu_items\(\): array/,
  /politica-editoriala/,
  /publicitate-si-consimtamant/,
  /politica-de-confidentialitate/,
  /disclaimer-culinar/,
]);

requireThemeIncludes('footer', 'footer menu fallback', [
  /fallback_cb'\s*=>\s*'kepoli_footer_menu_fallback'/,
]);

requireThemeIncludes('single', 'priority single post image', [
  /fetchpriority'\s*=>\s*'high'/,
  /loading'\s*=>\s*'eager'/,
]);

requireThemeIncludes('front-page', 'lazy author photo image', [
  /author-strip__photo[\s\S]*<img/,
  /writer-photo',\s*'jpg'/,
  /loading="lazy"/,
]);

requireThemeIncludes('page-despre-autor', 'priority author page image', [
  /author-strip__photo[\s\S]*<img/,
  /fetchpriority="high"/,
  /loading="eager"/,
]);

for (const pngPath of obsoleteThemePngs) {
  if (fs.existsSync(pngPath)) {
    failures.push(`Obsolete oversized theme PNG is still present: ${pngPath}`);
  }
}

if (themeAssetStats.heroJpg > 300000) {
  failures.push('Homepage hero JPEG is larger than the 300 KB production budget.');
}

if (themeAssetStats.socialCoverJpg > 450000) {
  failures.push('Social cover JPEG is larger than the 450 KB production budget.');
}

if (themeAssetStats.writerJpg > 150000) {
  failures.push('Author JPEG is larger than the 150 KB production budget.');
}

if (themeAssetStats.styleMinCss >= themeAssetStats.styleCss) {
  failures.push('Minified stylesheet is not smaller than style.css.');
}

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
  /function kepoli_related_card_reason\s*\(/,
  /function kepoli_post_next_steps\s*\(/,
  /function kepoli_article_snapshot_items\s*\(/,
  /function kepoli_editorial_paths\s*\(/,
  /function kepoli_article_freshness_label\s*\(/,
  /function kepoli_article_collection_meta_items\s*\(/,
  /function kepoli_recently_touched_posts_by_kind\s*\(/,
  /gallery/,
]);

requireThemeIncludes('front-page', 'homepage trust links', [
  /kepoli_render_reader_trust_links\s*\(/,
  /kepoli_render_post_card_meta\s*\(/,
  /kepoli_category_card_image_data\s*\(/,
  /kepoli_editorial_paths\s*\(/,
  /kepoli_recently_touched_posts_by_kind\s*\(/,
  /category-card__visual/,
  /category-card__gallery/,
  /guide-path-grid/,
  /review-grid/,
]);

requireThemeIncludes('page-articole', 'editorial discovery grouping', [
  /kepoli_editorial_paths\s*\(/,
  /guide-path-grid/,
  /kepoli_recently_touched_posts_by_kind\s*\(/,
  /review-grid/,
]);

for (const fileKey of ['single', 'archive', 'search', 'page', 'page-retete', 'page-articole']) {
  requireThemeIncludes(fileKey, 'reader trust links', [
    /kepoli_render_reader_trust_links\s*\(/,
  ]);
}

requireThemeIncludes('page-retete', 'category card visual proof', [
  /kepoli_category_card_image_data\s*\(/,
  /category-card__visual/,
  /category-card__gallery/,
]);

requireThemeIncludes('page-articole', 'freshness transparency meta', [
  /kepoli_article_collection_meta_items\s*\(/,
  /meta-strip/,
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

requireThemeIncludes('archive', 'article freshness archive note', [
  /kepoli_article_collection_meta_items\s*\(/,
]);

requireThemeIncludes('single', 'recipe snapshot support', [
  /kepoli_recipe_snapshot_items\s*\(/,
  /entry-recipe-snapshot/,
]);

requireThemeIncludes('single', 'article snapshot support', [
  /kepoli_article_snapshot_items\s*\(/,
  /entry-article-snapshot/,
]);

requireThemeIncludes('single', 'early featured image support', [
  /entry-featured-media--header/,
  /entry-featured-media--header[\s\S]*entry-summary/,
]);

requireThemeIncludes('single', 'editorial recommendation reasons', [
  /kepoli_related_card_reason\s*\(/,
  /related-card__reason/,
]);

requireThemeIncludes('single', 'post-end routing block', [
  /kepoli_post_next_steps\s*\(/,
  /entry-next-steps/,
  /entry-next-grid/,
]);

requireSeedIncludes('distinct intro support', [
  /function kepoli_seed_post_intro\s*\(/,
  /kepoli_seed_post_intro\(\$post\)/,
]);

requireSeedIncludes('article snapshot meta support', [
  /function kepoli_seed_article_snapshot_meta\s*\(/,
  /_kepoli_article_snapshot/,
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
