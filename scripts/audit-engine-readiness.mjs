import fs from 'node:fs';
import path from 'node:path';
import { spawnSync } from 'node:child_process';

const root = process.cwd();
const failures = [];
const notes = [];

const requiredFiles = [
  'README.md',
  '.env.example',
  '.gitignore',
  'docker-compose.yml',
  'site-brief.example.json',
  'content/site-profile.json',
  'content/pages.json',
  'content/categories.json',
  'content/posts.json',
  'content/image-plan.json',
  'docs/new-blog-launch-plan.md',
  'docs/replicate-food-blog.md',
  'docs/codex-new-site-prompt.md',
  'scripts/create-site-brief.mjs',
  'scripts/validate-site-brief.mjs',
  'scripts/start-new-blog.mjs',
  'scripts/prepare-replica.mjs',
  'scripts/reset-replica-content.mjs',
  'scripts/generate-replica-shell.mjs',
  'scripts/validate-new-blog.mjs',
  'scripts/audit-rebrand.mjs',
  'scripts/audit-replica-readiness.mjs',
  'scripts/audit-adsense-readiness.mjs',
  'seed/bootstrap.php',
  'wp-content/themes/kepoli/functions.php',
  'wp-content/themes/kepoli/header.php',
  'wp-content/themes/kepoli/footer.php',
  'wp-content/mu-plugins/kepoli-adtech.php',
  'wp-content/plugins/kepoli-author-tools/kepoli-author-tools.php',
  'wp-content/plugins/kepoli-author-tools/assets/admin.js',
];

for (const file of requiredFiles) {
  if (!fs.existsSync(path.join(root, file))) {
    failures.push(`Missing required engine file: ${file}`);
  }
}

const siteProfile = readJsonObject('content/site-profile.json');
const briefExample = readJsonObject('site-brief.example.json');
const gitignore = readFile('.gitignore');
const envExample = readFile('.env.example');
const dockerCompose = readFile('docker-compose.yml');
const readme = readFile('README.md');
const launchPlan = readFile('docs/new-blog-launch-plan.md');
const replicateDocs = readFile('docs/replicate-food-blog.md');
const codexPrompt = readFile('docs/codex-new-site-prompt.md');
const themeFunctions = readFile('wp-content/themes/kepoli/functions.php');
const themeHeader = readFile('wp-content/themes/kepoli/header.php');
const themeFooter = readFile('wp-content/themes/kepoli/footer.php');
const adtechMuPlugin = readFile('wp-content/mu-plugins/kepoli-adtech.php');
const authorToolsPhp = readFile('wp-content/plugins/kepoli-author-tools/kepoli-author-tools.php');
const authorToolsJs = readFile('wp-content/plugins/kepoli-author-tools/assets/admin.js');
const seedBootstrap = readFile('seed/bootstrap.php');
const createBrief = readFile('scripts/create-site-brief.mjs');
const validateBrief = readFile('scripts/validate-site-brief.mjs');
const startNewBlog = readFile('scripts/start-new-blog.mjs');
const prepareReplica = readFile('scripts/prepare-replica.mjs');
const generateShell = readFile('scripts/generate-replica-shell.mjs');
const replicaAudit = readFile('scripts/audit-replica-readiness.mjs');
const adsenseAudit = readFile('scripts/audit-adsense-readiness.mjs');

checkSiteProfile();
checkBriefContract();
checkCloneScripts();
checkThemeAndPlugins();
checkDocs();
checkEnvironment();
runWorkflowSmokeTests();

if (failures.length > 0) {
  console.error(`Engine readiness audit found ${failures.length} issue${failures.length === 1 ? '' : 's'}.`);
  for (const failure of failures) {
    console.error(`- ${failure}`);
  }
  process.exit(1);
}

console.log('Engine readiness audit OK.');
for (const note of notes) {
  console.log(`- ${note}`);
}

function checkSiteProfile() {
  requireObjectPath(siteProfile, ['brand', 'name'], 'content/site-profile.json brand.name');
  requireObjectPath(siteProfile, ['brand', 'tagline'], 'content/site-profile.json brand.tagline');
  requireObjectPath(siteProfile, ['brand', 'description'], 'content/site-profile.json brand.description');
  requireObjectPath(siteProfile, ['brand', 'site_email'], 'content/site-profile.json brand.site_email');
  requireObjectPath(siteProfile, ['writer', 'name'], 'content/site-profile.json writer.name');
  requireObjectPath(siteProfile, ['writer', 'email'], 'content/site-profile.json writer.email');
  requireObjectPath(siteProfile, ['writer', 'bio'], 'content/site-profile.json writer.bio');

  if (valueAt(siteProfile, ['locales', 'admin']) !== 'en_US') {
    failures.push('content/site-profile.json must keep locales.admin=en_US.');
  }

  if (valueAt(siteProfile, ['locales', 'force_admin']) !== true) {
    failures.push('content/site-profile.json must keep locales.force_admin=true.');
  }

  for (const key of ['wordmark', 'icon', 'social_cover']) {
    const asset = String(valueAt(siteProfile, ['assets', key]) || '').trim();
    if (!isSlug(asset)) {
      failures.push(`content/site-profile.json assets.${key} must be a lowercase extensionless basename.`);
    }
  }

  for (const key of ['home', 'recipes', 'guides', 'about', 'author', 'privacy', 'cookies', 'advertising', 'editorial', 'terms', 'disclaimer']) {
    const slug = String(valueAt(siteProfile, ['slugs', key]) || '').trim();
    if (!isSlug(slug)) {
      failures.push(`content/site-profile.json slugs.${key} must be a valid slug.`);
    }
  }
}

function checkBriefContract() {
  for (const key of [
    'brand',
    'domain',
    'language',
    'publicLocale',
    'writerName',
    'writerEmail',
    'siteEmail',
    'projectSlug',
    'wordmarkAsset',
    'iconAsset',
    'socialCoverAsset',
    'ezoicAdsTxtAccountId',
    'ezoicAdsTxtRedirectUrl',
  ]) {
    if (!(key in briefExample)) {
      failures.push(`site-brief.example.json is missing ${key}.`);
    }
  }

  requireText('validate-site-brief asset contract', validateBrief, [
    /wordmarkAsset/,
    /iconAsset/,
    /socialCoverAsset/,
    /ezoicAdsTxtRedirectUrl/,
  ]);
}

function checkCloneScripts() {
  requireText('create-site-brief engine options', createBrief, [
    /wordmarkAsset/,
    /iconAsset/,
    /socialCoverAsset/,
    /ezoicAdsTxtAccountId/,
    /ezoicAdsTxtRedirectUrl/,
  ]);

  requireText('start-new-blog option forwarding', startNewBlog, [
    /wordmarkAsset:\s*'wordmark-asset'/,
    /iconAsset:\s*'icon-asset'/,
    /socialCoverAsset:\s*'social-cover-asset'/,
    /ezoicAdsTxtAccountId:\s*'ezoic-adstxt-account-id'/,
    /ezoicAdsTxtRedirectUrl:\s*'ezoic-adstxt-redirect-url'/,
  ]);

  requireText('prepare-replica profile and env generation', prepareReplica, [
    /assets:\s*\{/,
    /wordmark:\s*wordmarkAsset/,
    /icon:\s*iconAsset/,
    /social_cover:\s*socialCoverAsset/,
    /WP_ADMIN_LOCALE/,
    /EZOIC_ADSTXT_ACCOUNT_ID/,
    /EZOIC_ADSTXT_REDIRECT_URL/,
  ]);

  requireText('generate-replica-shell profile generation', generateShell, [
    /assets:\s*\{/,
    /wordmark:\s*wordmarkAsset/,
    /icon:\s*iconAsset/,
    /social_cover:\s*socialCoverAsset/,
  ]);

  requireText('replica readiness profile-aware asset checks', replicaAudit, [
    /profileValue\(\['assets',\s*'wordmark'\]\)/,
    /profileValue\(\['assets',\s*'icon'\]\)/,
    /profileValue\(\['assets',\s*'social_cover'\]\)/,
    /hasAsset/,
  ]);
}

function checkThemeAndPlugins() {
  requireText('theme locale split and profile helpers', themeFunctions, [
    /function kepoli_public_locale\(\): string/,
    /function kepoli_admin_locale\(\): string/,
    /add_filter\('locale',\s*'kepoli_force_admin_locale'/,
    /function kepoli_wordmark_asset\(\): string/,
    /function kepoli_icon_asset\(\): string/,
    /function kepoli_social_cover_asset\(\): string/,
    /remove_action\('wp_head',\s*'rel_canonical'\)/,
    /function kepoli_resolve_profile_page_template\(string \$template\): string/,
  ]);

  requireText('header/footer use profile-driven wordmark', `${themeHeader}\n${themeFooter}`, [
    /kepoli_asset_uri\(kepoli_wordmark_asset\(\)\)/,
    /kepoli_asset_dimension_attributes\(kepoli_wordmark_asset\(\)\)/,
  ]);

  requireText('MU plugin profile-driven machine files', adtechMuPlugin, [
    /function kepoli_mu_site_name\(\): string/,
    /function kepoli_mu_asset_uri\(string \$key/,
    /EZOIC_ADSTXT_ACCOUNT_ID/,
    /EZOIC_ADSTXT_REDIRECT_URL/,
    /site\.webmanifest/,
    /kepoli_mu_public_locale\(\)/,
  ]);

  requireText('author tools admin/public locale split', `${authorToolsPhp}\n${authorToolsJs}`, [
    /admin_ui_text/,
    /public_content_text/,
    /adminIsEnglish/,
    /publicIsEnglish/,
    /PUBLIC_IS_ENGLISH/,
  ]);

  requireText('seed imports site profile contract', seedBootstrap, [
    /content\/site-profile\.json/,
    /kepoli_site_profile/,
    /\$normalized\['locales'\]\['admin'\]\s*=\s*'en_US'/,
    /\$normalized\['locales'\]\['force_admin'\]\s*=\s*true/,
    /update_user_meta\(\(int\) \$user->ID,\s*'locale',\s*kepoli_seed_admin_locale\(\)\)/,
  ]);
}

function checkDocs() {
  requireText('README clone handoff', readme, [
    /docs\/new-blog-launch-plan\.md/,
    /docs\/replicate-food-blog\.md/,
    /docs\/codex-new-site-prompt\.md/,
    /scripts\/start-new-blog\.mjs/,
  ]);

  requireText('launch plan complete path', launchPlan, [
    /site-brief\.json/,
    /scripts\/create-site-brief\.mjs/,
    /scripts\/start-new-blog\.mjs/,
    /Replace Public Identity Assets/,
    /Engine Readiness/,
  ]);

  requireText('replication docs asset/env contract', replicateDocs, [
    /content\/site-profile\.json/,
    /assets\.wordmark/,
    /EZOIC_ADSTXT_ACCOUNT_ID/,
    /scripts\/audit-engine-readiness\.mjs/,
  ]);

  requireText('Codex new-site prompt executable standard', codexPrompt, [
    /execute the work, not just the plan/i,
    /scripts\/create-site-brief\.mjs/,
    /scripts\/start-new-blog\.mjs/,
    /scripts\/validate-new-blog\.mjs/,
  ]);
}

function checkEnvironment() {
  requireText('.gitignore local clone artifacts', gitignore, [
    /\.replica-backups\//,
    /site-brief\.json/,
  ]);

  requireText('.env.example locale and ad defaults', envExample, [
    /WP_LOCALE=/,
    /WP_ADMIN_LOCALE=en_US/,
    /ADSENSE_ENABLE=0/,
    /GA_ENABLE=0/,
    /EZOIC_ADSTXT_ACCOUNT_ID=/,
    /EZOIC_ADSTXT_REDIRECT_URL=/,
  ]);

  requireText('docker-compose env forwarding', dockerCompose, [
    /WP_LOCALE:\s*\$\{WP_LOCALE:-/,
    /WP_ADMIN_LOCALE:\s*\$\{WP_ADMIN_LOCALE:-en_US\}/,
    /EZOIC_ADSTXT_ACCOUNT_ID:\s*\$\{EZOIC_ADSTXT_ACCOUNT_ID:-\}/,
    /EZOIC_ADSTXT_REDIRECT_URL:\s*\$\{EZOIC_ADSTXT_REDIRECT_URL:-\}/,
  ]);

  requireText('AdSense audit tracks profile-driven assets and env', adsenseAudit, [
    /content\/site-profile\.json/,
    /socialCoverAsset/,
    /EZOIC_ADSTXT/,
  ]);
}

function runWorkflowSmokeTests() {
  runNodeCheck('Validate example site brief', ['scripts/validate-site-brief.mjs', '--brief', 'site-brief.example.json']);
  runNodeCheck('Dry-run new-blog workflow from example brief', ['scripts/start-new-blog.mjs', '--brief', 'site-brief.example.json']);
  notes.push('Example brief and dry-run clone workflow passed.');
}

function readFile(relativePath) {
  const absolutePath = path.join(root, relativePath);
  if (!fs.existsSync(absolutePath)) return '';
  return fs.readFileSync(absolutePath, 'utf8');
}

function readJsonObject(relativePath) {
  const content = readFile(relativePath);
  if (!content) return {};

  try {
    const value = JSON.parse(content);
    if (!value || typeof value !== 'object' || Array.isArray(value)) {
      failures.push(`${relativePath} must contain a JSON object.`);
      return {};
    }

    return value;
  } catch (error) {
    failures.push(`${relativePath} is invalid JSON: ${error.message}`);
    return {};
  }
}

function requireObjectPath(source, pathParts, label) {
  const value = valueAt(source, pathParts);
  if (typeof value !== 'string' || value.trim() === '') {
    failures.push(`${label} is required.`);
  }
}

function valueAt(source, pathParts) {
  let value = source;
  for (const part of pathParts) {
    if (!value || typeof value !== 'object' || !(part in value)) return undefined;
    value = value[part];
  }

  return value;
}

function requireText(label, content, patterns) {
  if (!content) {
    failures.push(`${label} could not be checked because the file content was empty.`);
    return;
  }

  for (const pattern of patterns) {
    if (!pattern.test(content)) {
      failures.push(`${label} is missing: ${pattern}`);
    }
  }
}

function runNodeCheck(label, args) {
  const result = spawnSync(process.execPath, args, {
    cwd: root,
    encoding: 'utf8',
    stdio: 'pipe',
  });

  if (result.status === 0) return;

  const output = [result.stdout, result.stderr]
    .filter(Boolean)
    .join('\n')
    .trim()
    .split(/\r?\n/)
    .slice(0, 12)
    .join('\n');

  failures.push(`${label} failed: ${output || `exit ${result.status}`}`);
}

function isSlug(value) {
  return /^[a-z0-9]+(?:-[a-z0-9]+)*$/.test(String(value || ''));
}
