(function () {
  const PAGE_BREAK = '<!--nextpage-->';
  const CONFIG = window.kepoliAuthorTools || {};
  const SITE_NAME = CONFIG.siteName || 'Food Blog';
  const ADMIN_IS_ENGLISH = CONFIG.adminIsEnglish !== undefined ? !!CONFIG.adminIsEnglish : true;
  const PUBLIC_IS_ENGLISH = CONFIG.publicIsEnglish !== undefined ? !!CONFIG.publicIsEnglish : !!CONFIG.isEnglish;
  const GUIDES_SLUG = String(CONFIG.guidesSlug || '').trim().toLowerCase();

  function contentText(ro, en) {
    return PUBLIC_IS_ENGLISH ? en : ro;
  }

  function getTextarea() {
    return document.getElementById('content');
  }

  function activeVisualEditor() {
    if (!window.tinymce || !window.tinymce.get) {
      return null;
    }

    const editor = window.tinymce.get('content');
    if (!editor || editor.isHidden()) {
      return null;
    }

    return editor;
  }

  function insertContent(content) {
    const editor = activeVisualEditor();
    const textarea = getTextarea();

    if (editor) {
      editor.insertContent(content);
      editor.nodeChanged();
      return;
    }

    if (textarea) {
      insertAtCursor(textarea, content);
    }
  }

  function cleanText(value) {
    const div = document.createElement('div');
    div.innerHTML = value || '';
    return (div.textContent || div.innerText || '')
      .replace(/\s+/g, ' ')
      .replace(/\s+([,.!?;:])/g, '$1')
      .trim();
  }

  function currentTitle() {
    const title = document.getElementById('title');
    return title ? title.value.trim() : '';
  }

  function currentKind() {
    const checked = document.querySelector('input[name="kepoli_post_kind"]:checked');
    return checked ? checked.value : 'recipe';
  }

  function currentContentText() {
    const editor = activeVisualEditor();
    const textarea = getTextarea();

    if (editor) {
      return cleanText(editor.getContent({ format: 'html' }));
    }

    return textarea ? cleanText(textarea.value) : '';
  }

  function currentContentHtml() {
    const editor = activeVisualEditor();
    const textarea = getTextarea();

    if (editor) {
      return String(editor.getContent({ format: 'html' }) || '');
    }

    return textarea ? String(textarea.value || '') : '';
  }

  function contentHasMarkup(value) {
    return /<[^>]+>/.test(String(value || ''));
  }

  function currentFieldValue(selector) {
    const field = document.querySelector(selector);
    return field ? String(field.value || '').trim() : '';
  }

  function currentSlugValue() {
    const editable = document.getElementById('editable-post-name-full');
    if (editable && String(editable.textContent || '').trim()) {
      return String(editable.textContent || '').trim();
    }

    const sample = document.querySelector('#sample-permalink a, #sample-permalink');
    if (sample) {
      const text = cleanText(sample.textContent || '');
      const parts = text.split('/');
      return parts.length ? String(parts[parts.length - 1] || '').trim() : '';
    }

    return '';
  }

  function categoryInputs() {
    return Array.from(document.querySelectorAll('#categorychecklist input[type="checkbox"][name="post_category[]"]'));
  }

  function tagField() {
    return document.querySelector('textarea[name="tax_input[post_tag]"], #tax-input-post_tag, .tagsdiv textarea.the-tags');
  }

  function currentTags() {
    const field = tagField();
    if (!field) {
      return [];
    }

    return String(field.value || '')
      .split(',')
      .map((item) => item.trim())
      .filter(Boolean);
  }

  function selectedCategoryIds() {
    return categoryInputs()
      .filter((input) => input.checked)
      .map((input) => Number.parseInt(input.value, 10))
      .filter((value) => Number.isFinite(value));
  }

  function hasManualCategorySelection() {
    return !!(window.kepoliAuthorToolsState && window.kepoliAuthorToolsState.categoryManual);
  }

  function oncePerSessionFlag(key) {
    if (!window.kepoliAuthorToolsState) {
      window.kepoliAuthorToolsState = {};
    }

    return window.kepoliAuthorToolsState[key];
  }

  function setSessionFlag(key, value) {
    if (!window.kepoliAuthorToolsState) {
      window.kepoliAuthorToolsState = {};
    }

    window.kepoliAuthorToolsState[key] = value;
  }

  function hasFeaturedImage() {
    const thumbnailInput = document.getElementById('_thumbnail_id');
    return !!(thumbnailInput && String(thumbnailInput.value || '').trim() && String(thumbnailInput.value) !== '-1');
  }

  function parseListField(selector) {
    return currentFieldValue(selector)
      .split(/[\n,]+/)
      .map((item) => item.trim())
      .filter(Boolean);
  }

  function hasInContentInternalLinks() {
    const html = currentContentHtml();
    if (!html) {
      return false;
    }

    const wrapper = document.createElement('div');
    wrapper.innerHTML = html;
    const links = Array.from(wrapper.querySelectorAll('a[href]'));

    return links.some((link) => {
      const href = String(link.getAttribute('href') || '').trim();
      if (!href || href.startsWith('#') || href.startsWith('mailto:') || href.startsWith('tel:')) {
        return false;
      }

      if (href.startsWith('/')) {
        return true;
      }

      try {
        const url = new URL(href, window.location.origin);
        return url.origin === window.location.origin;
      } catch (error) {
        return false;
      }
    });
  }

  function setStatus(message) {
    const targets = document.querySelectorAll('[data-kepoli-automation-status], [data-kepoli-companion-status]');
    if (!targets.length) {
      return;
    }

    targets.forEach((status) => {
      status.textContent = message;
    });

    window.setTimeout(() => {
      targets.forEach((status) => {
        status.textContent = '';
      });
    }, 2600);
  }

  function setField(selector, value) {
    const field = document.querySelector(selector);
    if (!field || !value) {
      return;
    }

    field.value = value;
    field.dispatchEvent(new Event('input', { bubbles: true }));
    field.dispatchEvent(new Event('change', { bubbles: true }));
  }

  function setFieldIfEmpty(selector, value) {
    if (!value) {
      return;
    }

    const field = document.querySelector(selector);
    if (!field) {
      return;
    }

    const currentValue = String(field.value || '').trim();
    const numericZero = field.type === 'number' && Number.parseFloat(currentValue || '0') === 0;
    if (currentValue && !numericZero) {
      return;
    }

    setField(selector, value);
  }

  function shortSentence(text, maxLength) {
    const clean = cleanText(text);
    if (clean.length <= maxLength) {
      return clean;
    }

    const slice = clean.slice(0, maxLength + 1);
    const sentenceEnd = Math.max(slice.lastIndexOf('.'), slice.lastIndexOf('!'), slice.lastIndexOf('?'));
    const wordEnd = slice.lastIndexOf(' ');
    const end = sentenceEnd > 90 ? sentenceEnd + 1 : Math.max(80, wordEnd);

    return `${slice.slice(0, end).replace(/[,:;\s]+$/, '')}...`;
  }

  function normalizeWords(text) {
    const stopwords = new Set([
      'acest', 'aceasta', 'aceste', 'acasa', 'aici', 'ale', 'are', 'care', 'cand', 'cum',
      'din', 'este', 'fara', 'mai', 'mult', 'pentru', 'prin', 'sau', 'sunt', 'unde',
      'un', 'una', 'unei', 'unui', 'reteta', 'retete', 'romanesc', 'romaneasca', 'kepoli',
      'the', 'and', 'with', 'from'
    ]);

    return cleanText(text)
      .toLowerCase()
      .normalize('NFD')
      .replace(/[\u0300-\u036f]/g, '')
      .replace(/[^a-z0-9\s-]/g, ' ')
      .split(/\s+/)
      .filter((word) => word.length > 3 && !stopwords.has(word));
  }

  function detectLanguage(text) {
    const clean = ` ${cleanText(text).toLowerCase()} `;
    if (!clean.trim()) {
      return 'unknown';
    }

    let romanianScore = /[Ã„Æ’ÃƒÂ¢ÃƒÂ®Ãˆâ„¢Ã…Å¸Ãˆâ€ºÃ…Â£]/.test(clean) ? 4 : 0;
    let englishScore = 0;
    const romanianMarkers = [' si ', ' din ', ' pentru ', ' cu ', ' este ', ' sunt ', ' reteta ', ' articol ', ' gatit ', ' ciocolata ', ' desert '];
    const englishMarkers = [' the ', ' and ', ' with ', ' from ', ' history ', ' guide ', ' recipe ', ' article ', ' chocolate ', ' sweet '];

    romanianMarkers.forEach((marker) => {
      if (clean.includes(marker)) {
        romanianScore += 2;
      }
    });

    englishMarkers.forEach((marker) => {
      if (clean.includes(marker)) {
        englishScore += 2;
      }
    });

    if (romanianScore === 0 && englishScore === 0) {
      return 'unknown';
    }

    if (romanianScore >= englishScore + 2) {
      return 'ro';
    }

    if (englishScore >= romanianScore + 2) {
      return 'en';
    }

    return 'unknown';
  }

  function cleanedSlugFromTitle(title) {
    const stopwords = new Set([
      'si', 'sau', 'din', 'de', 'la', 'cu', 'pentru', 'despre', 'care', 'este', 'sunt',
      'the', 'and', 'with', 'from', 'into', 'your', 'this', 'that', 'history', 'fascinating',
      'what', 'when', 'where', 'how', 'why', 'guide', 'tips', 'best', 'more'
    ]);

    const parts = cleanText(title)
      .toLowerCase()
      .normalize('NFD')
      .replace(/[\u0300-\u036f]/g, '')
      .replace(/[^a-z0-9\s-]/g, ' ')
      .split(/\s+/)
      .map((part) => part.trim())
      .filter(Boolean);

    const kept = [];
    parts.forEach((part) => {
      if (!stopwords.has(part) && kept.length < 8) {
        kept.push(part);
      }
    });

    return kept.join('-');
  }

  function isSlugClean(title, slug) {
    if (!slug) {
      return true;
    }

    const normalizedSlug = String(slug || '').trim().toLowerCase();
    if (!normalizedSlug) {
      return true;
    }

    const expected = cleanedSlugFromTitle(title);
    if (expected && normalizedSlug === expected) {
      return true;
    }

    const parts = normalizedSlug.split('-').filter(Boolean);
    if (parts.length > 8 || normalizedSlug.length > 60) {
      return false;
    }

    const filler = new Set([
      'the', 'and', 'with', 'from', 'into', 'your', 'this', 'that', 'history', 'fascinating',
      'what', 'when', 'where', 'how', 'why', 'best', 'more'
    ]);

    const fillerCount = parts.filter((part) => filler.has(part)).length;
    return fillerCount <= 1;
  }

  function preferredCategoryNames() {
    const selected = categoryInputs()
      .filter((input) => input.checked)
      .map((input) => {
        const label = input.closest('label');
        return label ? cleanText(label.textContent || '') : '';
      })
      .filter(Boolean);

    if (selected.length) {
      return selected;
    }

    const suggestion = suggestedCategory();
    return suggestion ? [suggestion.name] : [];
  }

  function isEditorialCategoryLabel(value) {
    const normalized = cleanText(value).toLowerCase();
    return normalized === 'articole'
      || normalized === GUIDES_SLUG
      || normalized.includes('article')
      || normalized.includes('guide');
  }

  function scorePost(post, sourceWords, preferredCategories = []) {
    const haystack = normalizeWords([
      post.title,
      post.excerpt,
      (post.categories || []).join(' '),
      (post.tags || []).join(' ')
    ].join(' '));
    const hay = new Set(haystack);
    let score = 0;

    sourceWords.forEach((word) => {
      if (hay.has(word)) {
        score += 3;
      } else if (haystack.some((candidate) => candidate.includes(word) || word.includes(candidate))) {
        score += 1;
      }
    });

    const normalizedPreferredCategories = preferredCategories.map((category) => cleanText(category).toLowerCase());
    const normalizedPostCategories = (post.categories || []).map((category) => cleanText(category).toLowerCase());

    normalizedPreferredCategories.forEach((category) => {
      if (normalizedPostCategories.includes(category)) {
        score += 12;
      } else if (!isEditorialCategoryLabel(category) && normalizedPostCategories.length) {
        score -= 2;
      }
    });

    const usageCount = Number(post.linkUsage || 0);
    if (Number.isFinite(usageCount) && usageCount > 0) {
      score -= Math.min(9, usageCount * 2);
    }

    return score;
  }

  function relatedSuggestions(kind) {
    const posts = (window.kepoliAuthorTools && window.kepoliAuthorTools.relatedPosts) || [];
    const sourceWords = normalizeWords(`${currentTitle()} ${currentContentText()}`);
    const preferredCategories = preferredCategoryNames();

    return posts
      .map((post) => ({ ...post, score: scorePost(post, sourceWords, preferredCategories) }))
      .filter((post) => post.slug && post.kind === kind)
      .sort((a, b) => b.score - a.score || a.title.localeCompare(b.title));
  }

  function generatedSeoTitle() {
    const title = currentTitle();
    return title ? shortSentence(title, 58).replace(/\.\.\.$/, '') : '';
  }

  function outlineHeadingTargets() {
    return [
      'Pe scurt',
      'Detalii despre reteta',
      'Ingrediente',
      'Mod de preparare',
      'Cum se serveste',
      'Sfaturi pentru o reteta reusita',
      'Sfaturi pentru reusita',
      'Variatii ale retetei',
      'Cum se pastreaza',
      'Cum pastrezi',
      'Intrebari frecvente',
      'Concluzie',
      'Ideea principala',
      'Ce merita retinut',
      'Cum aplici in bucatarie',
      'Legaturi utile',
      'What to know first',
      'Recipe details',
      'Ingredients',
      'Method',
      'How to serve it',
      'Success notes',
      'Variations',
      'Storage',
      'Frequently asked questions',
      'Conclusion',
      'Main idea',
      'What to remember',
      'How to use it in the kitchen',
      'Useful links'
    ];
  }

  function isOutlineHeading(text) {
    const heading = normalizedHeading(text);
    return heading !== '' && outlineHeadingTargets().some((candidate) => heading === normalizedHeading(candidate));
  }

  function isSummaryHeading(text) {
    const heading = normalizedHeading(text);
    return [
      'Pe scurt',
      'Ideea principala',
      'What to know first',
      'Main idea'
    ].some((candidate) => heading === normalizedHeading(candidate));
  }

  function recipeHtmlLines(html) {
    if (!html) {
      return [];
    }

    const prepared = String(html)
      .replace(/<br\s*\/?>/gi, '\n')
      .replace(/<\/(?:p|div|li)>/gi, '\n');
    const container = document.createElement('div');
    container.innerHTML = prepared;

    return String(container.textContent || container.innerText || '')
      .replace(/\r/g, '')
      .split('\n')
      .map((line) => line.replace(/\s+/g, ' ').trim())
      .filter(Boolean);
  }

  function recipeSourceLines(value, preserveEmpty = false) {
    let source = String(value || '');

    if (contentHasMarkup(source)) {
      source = source
        .replace(/<br\s*\/?>/gi, '\n')
        .replace(/<\/(?:p|div|li|h[1-6]|ul|ol)>/gi, '\n');
      const container = document.createElement('div');
      container.innerHTML = source;
      source = String(container.textContent || container.innerText || '');
    }

    const lines = source
      .replace(/\r/g, '')
      .split('\n')
      .map((line) => line.replace(/\s+/g, ' ').trim());

    return preserveEmpty ? lines : lines.filter(Boolean);
  }

  function cleanRecipeItemLine(text, sectionName) {
    let value = String(text || '')
      .replace(/\s+/g, ' ')
      .trim();

    if (!value) {
      return '';
    }

    value = value
      .replace(/^(?:[-*\u2022]+)\s*/u, '')
      .replace(/^\d+\s*(?:[)-]\s*|\.\s+)/u, '');

    if (sectionName === 'steps') {
      value = value.replace(/^(?:pasul|step)\s*\d+\s*[:.)-]?\s*/iu, '');
    }

    return value.trim();
  }

  function parsedRecipeSections() {
    const lines = recipeSourceLines(currentContentHtml(), true);
    const sections = [];
    let current = null;

    lines.forEach((line) => {
      const value = String(line || '').trim();
      if (!value) {
        if (current) {
          current.lines.push('');
        }
        return;
      }

      if (isOutlineHeading(value)) {
        current = { heading: value, key: normalizedHeading(value), lines: [] };
        sections.push(current);
        return;
      }

      if (!current) {
        current = { heading: '', key: '_intro', lines: [] };
        sections.push(current);
      }

      current.lines.push(value);
    });

    return sections;
  }

  function recipeExtractionText() {
    const html = currentContentHtml();
    if (!html) {
      return '';
    }

    const container = document.createElement('div');
    container.innerHTML = html;

    container.querySelectorAll('br').forEach((node) => {
      node.replaceWith('\n');
    });

    container.querySelectorAll('li').forEach((node) => {
      node.prepend('\n- ');
      node.append('\n');
    });

    container.querySelectorAll('p, div, section, article, header, footer, ul, ol, blockquote, table, tr').forEach((node) => {
      node.append('\n');
    });

    container.querySelectorAll('h1, h2, h3, h4, h5, h6').forEach((node) => {
      node.prepend('\n');
      node.append('\n');
    });

    return String(container.textContent || html)
      .replace(/\r/g, '\n')
      .replace(/\u00a0/g, ' ')
      .replace(/[ \t]+/g, ' ')
      .replace(/\n[ \t]+/g, '\n')
      .replace(/[ \t]+\n/g, '\n')
      .replace(/\n{3,}/g, '\n\n')
      .trim();
  }

  function recipeExtractionLines() {
    return recipeExtractionText()
      .split(/\n+/)
      .map((line) => line.trim())
      .filter(Boolean);
  }

  function stripRecipeListMarker(line) {
    return cleanText(line)
      .replace(/^[\s>*\u2022\u00b7-]+/u, '')
      .replace(/^\d{1,2}\s*[.)-]\s*/u, '')
      .replace(/^\([a-z0-9]+\)\s*/i, '')
      .trim();
  }

  function canonicalRecipeHeading(line) {
    const heading = normalizedHeading(stripRecipeListMarker(line).replace(/:$/, ''));

    if (/^(ingredients?|ingredient list|ingredient checklist|what you need|ingrediente|lista ingrediente)$/.test(heading)) {
      return 'ingredients';
    }

    if (/^(method|instructions?|directions?|preparation|preparation method|steps?|cooking steps?|mod de preparare|preparare|pasi|pasii|instructiuni)$/.test(heading)) {
      return 'steps';
    }

    if (/^(recipe details?|details?|what to know first|serving ideas?|serving notes?|serving|how to serve|success notes?|tips?|storage|storage and reheating|variations?|common mistakes?|faq|frequently asked questions?|conclusion|notes?|nutrition|nutritional values?|pe scurt|detalii despre reteta|cum se serveste|cum servesti|sfaturi|cum pastrezi|variante|intrebari frecvente|concluzie)$/.test(heading)) {
      return 'stop';
    }

    return '';
  }

  function looksLikeRecipeMetaLine(line) {
    return /^(prep|preparation|rest|cook|cooking|bake|baking|total|servings?|serves|makes|yield|difficulty|timp|portii|nivel)\b/i.test(stripRecipeListMarker(line));
  }

  function extractRecipeSectionFromLines(sectionName) {
    const items = [];
    let active = false;

    recipeExtractionLines().forEach((line) => {
      const canonicalHeading = canonicalRecipeHeading(line);

      if (canonicalHeading === sectionName) {
        active = true;
        return;
      }

      if (active && canonicalHeading && canonicalHeading !== sectionName) {
        active = false;
        return;
      }

      if (!active || looksLikeRecipeMetaLine(line)) {
        return;
      }

      const itemText = cleanRecipeItemLine(stripRecipeListMarker(line), sectionName);
      if (itemText) {
        items.push(itemText);
      }
    });

    return Array.from(new Set(items)).slice(0, sectionName === 'ingredients' ? 40 : 30);
  }

  function summarySourceText() {
    const html = currentContentHtml();
    if (!html) {
      return currentContentText() || currentTitle();
    }

    const container = document.createElement('div');
    container.innerHTML = html;

    const paragraphs = [];
    let captureSummary = false;
    let beforeFirstHeading = true;

    for (const node of Array.from(container.childNodes)) {
      if (node.nodeType !== Node.ELEMENT_NODE) {
        continue;
      }

      const element = node;
      const tag = element.tagName.toLowerCase();
      const text = cleanText(element.textContent || '');
      if (!text) {
        continue;
      }

      const headingLike = /^h[1-6]$/.test(tag) || (tag === 'p' && isOutlineHeading(text));
      if (headingLike) {
        if (isSummaryHeading(text)) {
          captureSummary = true;
          beforeFirstHeading = false;
          continue;
        }

        if (captureSummary) {
          break;
        }

        beforeFirstHeading = false;
        continue;
      }

      if (captureSummary || beforeFirstHeading) {
        paragraphs.push(text);
        if (paragraphs.length >= 2) {
          break;
        }
      }
    }

    return paragraphs.join(' ') || currentContentText() || currentTitle();
  }

  function generatedExcerpt() {
    const text = summarySourceText();
    const title = currentTitle();
    return shortSentence(text || title, 220);
  }

  function generatedMetaDescription() {
    const text = summarySourceText();
    const title = currentTitle();
    return shortSentence(text || title, 155);
  }

  function generatedRelated() {
    const kind = currentKind();
    const recipeSuggestions = relatedSuggestions('recipe');
    const articleSuggestions = relatedSuggestions('article');

    if (kind === 'article' && recipeSuggestions.length && articleSuggestions.length) {
      return {
        recipes: [recipeSuggestions[0].slug],
        articles: [articleSuggestions[0].slug]
      };
    }

    return {
      recipes: recipeSuggestions.slice(0, kind === 'recipe' ? 3 : 5).map((post) => post.slug),
      articles: articleSuggestions.slice(0, kind === 'recipe' ? 1 : 2).map((post) => post.slug)
    };
  }

  function generatedImageMeta() {
    const title = currentTitle() || contentText(`Reteta ${SITE_NAME}`, `${SITE_NAME} recipe`);
    const kind = currentKind();
    const prefix = kind === 'article'
      ? contentText('Imagine editoriala pentru', 'Editorial image for')
      : contentText('Fotografie culinara pentru', 'Food photo for');

    return {
      alt: shortSentence(`${prefix} ${title}, ${contentText(`publicata pe ${SITE_NAME}.`, `published on ${SITE_NAME}.`)}`, 150),
      title: title,
      caption: shortSentence(contentText(`${title} pe ${SITE_NAME}.`, `${title} on ${SITE_NAME}.`), 120),
      description: shortSentence(contentText(`Imagine reprezentativa pentru ${title}, folosita in articolul culinar ${SITE_NAME}.`, `Representative image for ${title}, used in a ${SITE_NAME} food article.`), 220)
    };
  }

  function dedupeTags(tags) {
    const seen = new Set();
    return tags.map(cleanTagValue).filter((tag) => {
      const key = tag.toLowerCase();
      if (!tag || seen.has(key)) {
        return false;
      }

      seen.add(key);
      return true;
    });
  }

  function cleanTagValue(tag) {
    const clean = cleanText(tag)
      .replace(/^[,;:.!?"'“”‘’\s]+|[,;:.!?"'“”‘’\s]+$/g, '')
      .trim();

    if (!clean || clean.length > 70) {
      return '';
    }

    return clean;
  }

  function tagsLookStale(tags, sourceWords) {
    const current = dedupeTags(tags);
    if (!current.length || current.length > 5 || !sourceWords.length) {
      return false;
    }

    return current.every((tag) => {
      const words = normalizeWords(tag);
      return words.length > 0 && !words.some((word) => sourceWords.includes(word));
    });
  }

  function suggestedTags() {
    const posts = (window.kepoliAuthorTools && window.kepoliAuthorTools.relatedPosts) || [];
    const title = currentTitle();
    const text = currentContentText();
    const kind = currentKind();
    const sourceWords = normalizeWords(`${title} ${text}`);
    const matchedPosts = posts
      .map((post) => ({ ...post, score: scorePost(post, sourceWords) }))
      .filter((post) => post.score > 0)
      .sort((a, b) => b.score - a.score)
      .slice(0, 8);

    const tagScores = new Map();
    const seedTags = kind === 'article'
      ? (PUBLIC_IS_ENGLISH ? ['ingredients', 'kitchen tips', 'cooking techniques'] : ['ingrediente', 'organizare', 'tehnici'])
      : (PUBLIC_IS_ENGLISH ? ['recipes', 'home cooking'] : ['retete romanesti']);
    const lockedTags = new Set(seedTags.map((tag) => tag.toLowerCase()));

    seedTags.forEach((tag) => tagScores.set(tag, (tagScores.get(tag) || 0) + 1));

    matchedPosts.forEach((post) => {
      (post.tags || []).forEach((tag) => {
        tagScores.set(tag, (tagScores.get(tag) || 0) + Math.max(1, post.score));
      });
    });

    sourceWords.forEach((word) => {
      matchedPosts.forEach((post) => {
        (post.tags || []).forEach((tag) => {
          const normalizedTag = normalizeWords(tag);
          if (normalizedTag.includes(word)) {
            tagScores.set(tag, (tagScores.get(tag) || 0) + 2);
          }
        });
      });
    });

    const titleText = normalizeWords(title).join(' ');
    const quickTagMap = {
      ciorba: ['ciorba'],
      supa: ['supa'],
      soup: ['soup'],
      stew: ['stew', 'comfort food'],
      chicken: ['chicken', 'dinner'],
      burger: ['burger', 'cina rapida'],
      burgeri: ['burger', 'cina rapida'],
      sandvis: ['sandvis', 'pranz'],
      sandwich: ['sandwich', 'lunch'],
      paste: ['paste', 'cina rapida'],
      pasta: ['pasta', 'dinner'],
      rice: ['rice', 'side dish'],
      rosii: ['rosii'],
      tomate: ['rosii'],
      tomato: ['tomato'],
      mozzarella: ['mozzarella'],
      busuioc: ['busuioc'],
      basil: ['basil'],
      papanasi: ['papanasi', 'desert'],
      placinta: ['placinta', 'desert'],
      pie: ['pie', 'dessert'],
      cake: ['cake', 'dessert'],
      chocolate: ['chocolate', 'dessert'],
      cookies: ['cookies', 'dessert'],
      bread: ['bread', 'baking'],
      cozonac: ['cozonac', 'aluat'],
      zacusca: ['zacusca', 'conserve'],
      muraturi: ['muraturi', 'conserve'],
      ghid: ['ingrediente'],
      guide: ['ingredients', 'kitchen tips'],
      meniu: ['meniu', 'familie'],
      menu: ['menu', 'family meals'],
      aluat: ['aluat', 'patiserie'],
      dough: ['dough', 'baking'],
      sezon: ['sezon'],
      season: ['seasonal'],
      pastrare: ['pastrare', 'organizare'],
      storage: ['storage', 'kitchen tips']
    };

    Object.keys(quickTagMap).forEach((keyword) => {
      if (titleText.includes(keyword)) {
        quickTagMap[keyword].forEach((tag) => {
          tagScores.set(tag, (tagScores.get(tag) || 0) + 5);
          lockedTags.add(tag.toLowerCase());
        });
      }
    });

    Array.from(tagScores.keys()).forEach((tag) => {
      if (lockedTags.has(tag.toLowerCase())) {
        return;
      }

      const normalizedTag = normalizeWords(tag);
      if (!normalizedTag.length || !normalizedTag.some((word) => sourceWords.includes(word))) {
        tagScores.delete(tag);
      }
    });

    return dedupeTags(
      Array.from(tagScores.entries())
        .sort((a, b) => b[1] - a[1] || a[0].localeCompare(b[0]))
        .map(([tag]) => tag)
        .slice(0, 5)
    );
  }

  function applySuggestedTags(force) {
    const tags = suggestedTags();
    const field = tagField();
    const sourceWords = normalizeWords(`${currentTitle()} ${currentContentText()}`);

    if (!field || !tags.length) {
      return tags;
    }

    if (!force && currentTags().length > 0 && !tagsLookStale(currentTags(), sourceWords)) {
      return tags;
    }

    field.value = tags.join(', ');
    field.dispatchEvent(new Event('input', { bubbles: true }));
    field.dispatchEvent(new Event('change', { bubbles: true }));
    return tags;
  }

  function suggestedCategory() {
    const categories = (window.kepoliAuthorTools && window.kepoliAuthorTools.categories) || [];
    const isArticleCategory = (category) => {
      if (!category) {
        return false;
      }

      const categorySlug = String(category.slug || '').trim().toLowerCase();
      if (category.isArticle !== undefined) {
        return !!category.isArticle;
      }

      const label = normalizeWords([category.slug, category.name, category.description].join(' ')).join(' ');
      return categorySlug === GUIDES_SLUG
        || categorySlug === 'articole'
        || categorySlug === 'guides'
        || categorySlug === 'articles'
        || ['articol', 'articole', 'article', 'articles', 'guide', 'guides'].includes(label);
    };
    const articleCategory = categories.find((category) => isArticleCategory(category)) || null;

    if (currentKind() === 'article') {
      return articleCategory;
    }

    const recipeCategories = categories.filter((category) => !isArticleCategory(category));
    if (!recipeCategories.length) {
      return null;
    }

    const text = `${currentTitle()} ${currentContentText()}`;
    const titleWords = normalizeWords(currentTitle());
    const sourceWords = normalizeWords(text);
    const posts = (window.kepoliAuthorTools && window.kepoliAuthorTools.relatedPosts) || [];
    const categoryScores = new Map();
    const slugKeywords = {
      'ciorbe-si-supe': ['ciorba', 'bors', 'supa', 'supa crema', 'zeama', 'galuste', 'galuste', 'radauteana'],
      'feluri-principale': ['sarmale', 'tochitura', 'tocanita', 'friptura', 'mamaliga', 'ostropel', 'snitel', 'varza', 'pilaf', 'chiftele', 'paste', 'pasta', 'spaghetti', 'penne', 'fusilli', 'rigatoni', 'lasagna', 'risotto', 'burger', 'burgeri', 'sandvis', 'sandwich', 'wrap', 'pui', 'chicken'],
      'patiserie-si-deserturi': ['desert', 'prajitura', 'cozonac', 'placinta', 'clatite', 'papanasi', 'chec', 'cornulete', 'aluat', 'foi'],
      'conserve-si-garnituri': ['zacusca', 'muraturi', 'salata', 'garnitura', 'borcan', 'compot', 'bulion', 'gem', 'dulceata', 'piure'],
      'articole': ['ghid', 'cum', 'calendar', 'meniuri', 'tehnici', 'organizare', 'ingrediente', 'bucatarie', 'pastrare', 'explica']
    };
    if (GUIDES_SLUG && !slugKeywords[GUIDES_SLUG]) {
      slugKeywords[GUIDES_SLUG] = [...slugKeywords.articole];
    }
    const titleKeywordMap = {
      'ciorbe-si-supe': ['ciorba', 'bors', 'supa', 'supa crema', 'zeama'],
      'feluri-principale': ['paste', 'pasta', 'spaghetti', 'penne', 'fusilli', 'rigatoni', 'lasagna', 'risotto', 'pilaf', 'tocanita', 'friptura', 'snitel', 'burger', 'burgeri', 'sandvis', 'sandwich', 'wrap'],
      'patiserie-si-deserturi': ['desert', 'prajitura', 'cozonac', 'placinta', 'clatite', 'papanasi', 'chec', 'tort', 'cookies', 'cake', 'pie'],
      'conserve-si-garnituri': ['zacusca', 'muraturi', 'garnitura', 'salata', 'compot', 'gem', 'dulceata', 'bulion', 'piure']
    };
    const keywordGroups = [
      {
        match: ['soup', 'soups', 'stew', 'broth', 'ciorbe', 'supe'],
        terms: ['soup', 'soups', 'stew', 'broth', 'cream soup', 'comfort food']
      },
      {
        match: ['main', 'dinner', 'lunch', 'entree', 'feluri', 'principale'],
        terms: ['dinner', 'lunch', 'main dish', 'chicken', 'pasta', 'rice', 'stew', 'family meal']
      },
      {
        match: ['dessert', 'desserts', 'baking', 'pastry', 'sweet', 'patiserie'],
        terms: ['dessert', 'cake', 'chocolate', 'sweet', 'cookies', 'pie', 'pastry', 'baking', 'treat']
      },
      {
        match: ['side', 'sides', 'salad', 'preserve', 'preserves', 'garnituri', 'conserve'],
        terms: ['side dish', 'salad', 'preserves', 'pickle', 'jam', 'sauce', 'vegetables']
      },
      {
        match: ['article', 'articles', 'guide', 'guides', 'tips', 'how-to', 'howto', 'articole', GUIDES_SLUG].filter(Boolean),
        terms: ['guide', 'how', 'tips', 'history', 'explained', 'storage', 'pantry', 'ingredients', 'technique']
      }
    ];

    recipeCategories.forEach((category) => {
      let score = 0;
      const haystack = normalizeWords([category.name, category.description].join(' '));
      const categoryLabel = normalizeWords([category.slug, category.name, category.description].join(' ')).join(' ');
      const keywords = [...(slugKeywords[category.slug] || [])];

      keywordGroups.forEach((group) => {
        if (group.match.some((marker) => categoryLabel.includes(marker))) {
          keywords.push(...group.terms);
        }
      });

      sourceWords.forEach((word) => {
        if (haystack.includes(word)) {
          score += 2;
        }
      });

      keywords.forEach((keyword) => {
        const normalizedKeyword = normalizeWords(keyword).join(' ');
        if (!normalizedKeyword) {
          return;
        }

        if (normalizeWords(text).join(' ').includes(normalizedKeyword)) {
          score += 6;
        }
      });

      (titleKeywordMap[category.slug] || []).forEach((keyword) => {
        const normalizedKeyword = normalizeWords(keyword).join(' ');
        if (normalizedKeyword && normalizeWords(currentTitle()).join(' ').includes(normalizedKeyword)) {
          score += 12;
        }
      });

      posts.forEach((post) => {
        const postWords = normalizeWords([post.title, post.excerpt, (post.tags || []).join(' ')].join(' '));
        const overlap = titleWords.filter((word) => postWords.includes(word)).length;
        if (!overlap) {
          return;
        }

        (post.categories || []).forEach((categoryName) => {
          const matchingCategory = recipeCategories.find((item) => item.name === categoryName);
          if (matchingCategory && matchingCategory.id === category.id) {
            score += overlap * 2;
          }
        });
      });

      categoryScores.set(category.id, score);
    });

    return recipeCategories
      .map((category) => ({ ...category, score: categoryScores.get(category.id) || 0 }))
      .sort((a, b) => b.score - a.score || a.name.localeCompare(b.name))[0] || null;
  }

  function applySuggestedCategory(force) {
    const suggestion = suggestedCategory();
    if (!suggestion) {
      return null;
    }

    if (!force && hasManualCategorySelection()) {
      return suggestion;
    }

    categoryInputs().forEach((input) => {
      const checked = Number.parseInt(input.value, 10) === suggestion.id;
      input.checked = checked;
      input.dispatchEvent(new Event('change', { bubbles: true }));
    });

    return suggestion;
  }

  function normalizedHeading(text) {
    return cleanText(text)
      .toLowerCase()
      .normalize('NFD')
      .replace(/[\u0300-\u036f]/g, '')
      .replace(/[^a-z0-9\s]/g, ' ')
      .replace(/\s+/g, ' ')
      .trim();
  }

  function extractRecipeSection(sectionName) {
    const lineItems = extractRecipeSectionFromLines(sectionName);
    if (lineItems.length) {
      return lineItems;
    }

    const html = currentContentHtml();
    if (!html) {
      return [];
    }

    const container = document.createElement('div');
    container.innerHTML = html;

    const sectionHeadings = {
      ingredients: ['ingrediente', 'ingredients', 'ingredient list'],
      steps: ['mod de preparare', 'preparare', 'pasi', 'pasii']
    };

    const targetHeadings = [...(sectionHeadings[sectionName] || [])];
    if (sectionName === 'steps') {
      targetHeadings.push('method', 'instructions', 'directions', 'preparation', 'steps');
    }
    const sectionItems = [];
    let active = false;
    const outlineTargets = outlineHeadingTargets().map((candidate) => normalizedHeading(candidate));

    Array.from(container.childNodes).forEach((node) => {
      if (node.nodeType !== Node.ELEMENT_NODE) {
        return;
      }

      const element = node;
      const tag = element.tagName.toLowerCase();
      const text = cleanText(element.textContent || '');
      const headingLike = /^h[1-6]$/.test(tag) || (tag === 'p' && (outlineTargets.includes(normalizedHeading(text)) || targetHeadings.some((candidate) => normalizedHeading(text) === normalizedHeading(candidate))));

      if (headingLike) {
        const heading = normalizedHeading(text);
        active = targetHeadings.some((candidate) => heading === normalizedHeading(candidate));
        return;
      }

      if (!active) {
        return;
      }

      if (tag === 'ul' || tag === 'ol') {
        Array.from(element.querySelectorAll('li')).forEach((item) => {
          const itemText = cleanRecipeItemLine(cleanText(item.textContent || ''), sectionName);
          if (itemText) {
            sectionItems.push(itemText);
          }
        });
        return;
      }

      recipeHtmlLines(element.innerHTML || element.outerHTML).forEach((line) => {
        const itemText = cleanRecipeItemLine(line, sectionName);
        if (itemText) {
          sectionItems.push(itemText);
        }
      });
    });

    return Array.from(new Set(sectionItems));
  }

  function recipeSourceLinesFromText(text) {
    return String(text || '')
      .split(/\r\n|\r|\n/)
      .map((line) => cleanText(line))
      .filter(Boolean);
  }

  function durationToMinutes(value) {
    if (!value) {
      return 0;
    }

    const hoursMatch = value.match(/(\d{1,2})\s*(?:h|hr|hrs|ora|ore|hour|hours)\b/i);
    const minutesMatch = value.match(/(\d{1,3})\s*(?:m|min|mins|minute|minutes)\b/i);
    const hours = hoursMatch ? Number.parseInt(hoursMatch[1], 10) || 0 : 0;
    const minutes = minutesMatch ? Number.parseInt(minutesMatch[1], 10) || 0 : 0;

    if (!hours && !minutes) {
      const plainNumber = value.match(/(\d{1,3})/);
      return plainNumber ? Number.parseInt(plainNumber[1], 10) || 0 : 0;
    }

    return (hours * 60) + minutes;
  }

  function matchDurationFromLines(lines, labels) {
    const normalizedLabels = labels
      .map((label) => normalizedHeading(label))
      .filter(Boolean);

    for (const line of lines) {
      const normalizedLine = normalizedHeading(line);
      if (!normalizedLine) {
        continue;
      }

      for (const label of normalizedLabels) {
        if (normalizedLine === label || !normalizedLine.startsWith(`${label} `)) {
          continue;
        }

        const value = normalizedLine.slice(label.length).trim();
        if (value) {
          return durationToMinutes(value);
        }
      }
    }

    return 0;
  }

  function matchDurationInText(text, labels) {
    const quoted = labels.map((label) => label.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')).join('|');
    const pattern = new RegExp(`(?:^|[\\s(\\[-])(?:${quoted})(?![a-z])[^0-9]{0,32}((?:\\d{1,2}\\s*(?:h|hr|hrs|ora|ore|hour|hours)(?:\\s*\\d{1,3}\\s*(?:m|min|mins|minute|minutes))?)|(?:\\d{1,3}\\s*(?:m|min|mins|minute|minutes))|(?:\\d{1,3}))`, 'i');
    const match = text.match(pattern);
    return match ? durationToMinutes(match[1]) : 0;
  }

  function extractRecipeMetaFromText() {
    const text = currentContentText();
    const lines = recipeSourceLinesFromText(text);
    const servingsMatch = text.match(/(?:serves|servings|makes|yield|pentru|aproximativ|cam)?\s*(\d{1,2}\s*(?:servings?|people|persons|portii|persoane))/i);
    const prepMinutes = matchDurationFromLines(lines, ['timp de pregatire', 'timp pregatire', 'prep time', 'preparation time', 'prep']);
    const cookMinutes = matchDurationFromLines(lines, ['timp de gatire', 'timp gatire', 'cook time', 'cooking time', 'bake time', 'timp de coacere', 'timp de fierbere']);

    return {
      servings: servingsMatch ? servingsMatch[1] : '',
      prepMinutes: prepMinutes ? String(prepMinutes) : '',
      cookMinutes: cookMinutes ? String(cookMinutes) : '',
      totalMinutes: '',
      ingredients: extractRecipeSection('ingredients'),
      steps: extractRecipeSection('steps')
    };
  }

  function extractRecipeMetaFromTextRobust() {
    const text = currentContentText();
    const basic = extractRecipeMetaFromText();
    const lines = recipeSourceLinesFromText(text);
    const servingsMatch = text.match(/(?:serves|servings|makes|yield|portii|portie|persoane|pentru|aproximativ|cam)[^0-9]{0,24}(\d{1,2}(?:\s*(?:servings?|people|persons|portii|persoane))?)/i);
    let prepMinutes = matchDurationFromLines(lines, ['timp de pregatire', 'timp pregatire', 'prep time', 'preparation time', 'prep']);
    if (!prepMinutes) {
      prepMinutes = matchDurationInText(text, ['timp de pregatire', 'timp pregatire', 'prep time', 'preparation time', 'prep']);
    }

    let cookMinutes = matchDurationFromLines(lines, ['timp de gatire', 'timp gatire', 'cook time', 'cooking time', 'bake time', 'timp de coacere', 'timp de fierbere']);
    if (!cookMinutes) {
      cookMinutes = matchDurationInText(text, ['timp de gatire', 'timp gatire', 'cook time', 'cooking time', 'bake time', 'boil time', 'simmer time', 'timp de coacere', 'timp de fierbere']);
    }

    let totalMinutes = matchDurationFromLines(lines, ['timp total', 'total time', 'total']);
    if (!totalMinutes) {
      totalMinutes = matchDurationInText(text, ['timp total', 'total time', 'total']);
    }

    if (prepMinutes > 0 && cookMinutes === 0 && totalMinutes > prepMinutes) {
      cookMinutes = totalMinutes - prepMinutes;
    } else if (cookMinutes > 0 && prepMinutes === 0 && totalMinutes > cookMinutes) {
      prepMinutes = totalMinutes - cookMinutes;
    }

    return {
      servings: servingsMatch ? servingsMatch[1] : basic.servings,
      prepMinutes: prepMinutes ? String(prepMinutes) : basic.prepMinutes,
      cookMinutes: cookMinutes ? String(cookMinutes) : basic.cookMinutes,
      totalMinutes: totalMinutes ? String(totalMinutes) : basic.totalMinutes,
      ingredients: basic.ingredients,
      steps: basic.steps
    };
  }

  function fillRecipeSchema(extractOnlyIfEmpty) {
    const data = extractRecipeMetaFromTextRobust();
    const setter = extractOnlyIfEmpty ? setFieldIfEmpty : setField;
    if (!data.totalMinutes && (data.prepMinutes || data.cookMinutes)) {
      data.totalMinutes = String((Number.parseInt(data.prepMinutes || '0', 10) || 0) + (Number.parseInt(data.cookMinutes || '0', 10) || 0));
    }

    setter('input[name="kepoli_recipe_servings"]', data.servings);
    setter('input[name="kepoli_recipe_prep_minutes"]', data.prepMinutes);
    setter('input[name="kepoli_recipe_cook_minutes"]', data.cookMinutes);
    setter('input[name="kepoli_recipe_total_minutes"]', data.totalMinutes);
    setter('textarea[name="kepoli_recipe_ingredients"]', data.ingredients.join('\n'));
    setter('textarea[name="kepoli_recipe_steps"]', data.steps.join('\n'));

    return data;
  }

  function completeSetup() {
    setFieldIfEmpty('input[name="kepoli_seo_title"]', generatedSeoTitle());
    setFieldIfEmpty('textarea[name="kepoli_post_excerpt"]', generatedExcerpt());
    setFieldIfEmpty('textarea[name="kepoli_meta_description"]', generatedMetaDescription());

    const related = generatedRelated();
    setFieldIfEmpty('textarea[name="kepoli_related_recipe_slugs"]', related.recipes.join(', '));
    setFieldIfEmpty('textarea[name="kepoli_related_article_slugs"]', related.articles.join(', '));

    const imageMeta = generatedImageMeta();
    setFieldIfEmpty('input[name="kepoli_image_alt"]', imageMeta.alt);
    setFieldIfEmpty('input[name="kepoli_image_title"]', imageMeta.title);
    setFieldIfEmpty('input[name="kepoli_image_caption"]', imageMeta.caption);
    setFieldIfEmpty('textarea[name="kepoli_image_description"]', imageMeta.description);

    applySuggestedCategory(false);
    applySuggestedTags(false);

    if (currentKind() === 'recipe') {
      fillRecipeSchema(true);
    }
  }

  function completeSetupIfReady(reason, showStatus) {
    const title = currentTitle();
    const content = currentContentText();

    if (title.length < 6) {
      return false;
    }

    if (content.length < 80 && reason !== 'kind') {
      return false;
    }

    completeSetup();

    if (showStatus) {
      setStatus('Empty fields were filled from the current title and content.');
    }

    return true;
  }

  function bindAutomationButtons() {
    const setupButton = document.querySelector('[data-kepoli-complete-setup]');
    const categoryButton = document.querySelector('[data-kepoli-suggest-category]');
    const tagsButton = document.querySelector('[data-kepoli-suggest-tags]');
    const recipeButton = document.querySelector('[data-kepoli-extract-recipe]');
    const excerptButton = document.querySelector('[data-kepoli-generate-excerpt]');
    const metaButton = document.querySelector('[data-kepoli-generate-meta]');
    const relatedButton = document.querySelector('[data-kepoli-suggest-related]');
    const imageButton = document.querySelector('[data-kepoli-generate-image-meta]');

    if (setupButton) {
      setupButton.addEventListener('click', () => {
        completeSetup();
        setStatus('Empty fields were filled automatically. Review the result before publishing.');
      });
    }

    if (categoryButton) {
      categoryButton.addEventListener('click', () => {
        const suggestion = applySuggestedCategory(true);
        setStatus(
          suggestion
            ? `Suggested category selected: ${suggestion.name}.`
            : 'No clear category was found in the content.'
        );
      });
    }

    if (tagsButton) {
      tagsButton.addEventListener('click', () => {
        const tags = applySuggestedTags(true);
        setStatus(
          tags.length
            ? `Suggested tags were filled: ${tags.join(', ')}.`
            : 'Not enough clear signals were found for tags.'
        );
      });
    }

    if (recipeButton) {
      recipeButton.addEventListener('click', () => {
        const data = fillRecipeSchema(false);
        const hasData = data.ingredients.length || data.steps.length || data.servings || data.prepMinutes || data.cookMinutes || data.totalMinutes;
        setStatus(
          hasData
            ? 'Recipe schema was extracted from the content. Review ingredients, steps, and times.'
            : 'Not enough recipe signals were found. Use Ingredients and Method headings, or fill the fields manually.'
        );
      });
    }

    if (excerptButton) {
      excerptButton.addEventListener('click', () => {
        setField('textarea[name="kepoli_post_excerpt"]', generatedExcerpt());
        setStatus('Excerpt generated. Adjust it if you want a more editorial summary.');
      });
    }

    if (metaButton) {
      metaButton.addEventListener('click', () => {
        setField('textarea[name="kepoli_meta_description"]', generatedMetaDescription());
        setStatus('Meta description generated. Review it before publishing.');
      });
    }

    if (relatedButton) {
      relatedButton.addEventListener('click', () => {
        const related = generatedRelated();

        setField('textarea[name="kepoli_related_recipe_slugs"]', related.recipes.join(', '));
        setField('textarea[name="kepoli_related_article_slugs"]', related.articles.join(', '));
        setStatus('Internal links suggested. Adjust the list if you want different recommendations.');
      });
    }

    if (imageButton) {
      imageButton.addEventListener('click', () => {
        const imageMeta = generatedImageMeta();

        setField('input[name="kepoli_image_alt"]', imageMeta.alt);
        setField('input[name="kepoli_image_title"]', imageMeta.title);
        setField('input[name="kepoli_image_caption"]', imageMeta.caption);
        setField('textarea[name="kepoli_image_description"]', imageMeta.description);
        setStatus('Image metadata generated. Check that it describes the selected image correctly.');
      });
    }
  }

  function insertAtCursor(textarea, text) {
    const start = textarea.selectionStart || 0;
    const end = textarea.selectionEnd || 0;
    const before = textarea.value.slice(0, start);
    const after = textarea.value.slice(end);

    textarea.value = `${before}${text}${after}`;
    textarea.selectionStart = textarea.selectionEnd = start + text.length;
    textarea.focus();
    textarea.dispatchEvent(new Event('input', { bubbles: true }));
  }

  function isHeadingBlock(block) {
    if (/^<h[1-6]\b/i.test(block)) {
      return true;
    }

    return isOutlineHeading(cleanText(String(block || '').replace(/:$/, '')));
  }

  function preferredTextBreakIndexes(blocks) {
    return blocks.reduce((indexes, block, index) => {
      if (index === 0 || !isHeadingBlock(block)) {
        return indexes;
      }

      if (/^<h[23]\b/i.test(block)) {
        indexes.push(index);
      }

      return indexes;
    }, []);
  }

  function blockWordCount(block) {
    const plain = String(block || '').replace(/<[^>]*>/g, ' ').replace(/\s+/g, ' ').trim();
    return Math.max(1, plain ? plain.split(/\s+/).length : 1);
  }

  function splitContentBlocks(content) {
    const clean = String(content || '').replace(/<!--\s*nextpage\s*-->/gi, '').trim();
    if (!clean) {
      return [];
    }

    const byParagraph = clean.split(/\n{2,}/).map((block) => block.trim()).filter(Boolean);
    if (byParagraph.length > 1) {
      return byParagraph;
    }

    const wrapper = document.createElement('div');
    wrapper.innerHTML = clean;
    const htmlBlocks = Array.from(wrapper.childNodes)
      .filter((node) => {
        if (node.nodeType === Node.TEXT_NODE) {
          return String(node.textContent || '').trim() !== '';
        }

        return node.nodeType === Node.ELEMENT_NODE;
      })
      .map((node) => {
        if (node.nodeType === Node.TEXT_NODE) {
          return String(node.textContent || '').trim();
        }

        return String(node.outerHTML || node.textContent || '').trim();
      })
      .filter(Boolean);

    if (htmlBlocks.length > 1) {
      return htmlBlocks;
    }

    const byLine = clean.split(/\r\n|\r|\n/).map((line) => line.trim()).filter(Boolean);
    if (byLine.length > 1) {
      return byLine;
    }

    return canUseSentenceSplitFallback(clean) ? sentenceContentBlocks(clean, 2) : byParagraph;
  }

  function canUseSentenceSplitFallback(content) {
    const clean = String(content || '').replace(/<!--\s*nextpage\s*-->/gi, '').trim();
    if (!clean) {
      return false;
    }

    // Sentence chunks are plain text only. Formatted posts must keep their
    // existing paragraphs, headings, lists, and line breaks.
    if (/<[^>]+>/.test(clean)) {
      return false;
    }

    return !/[\r\n]/.test(clean);
  }

  function sentenceContentBlocks(content, parts) {
    const plain = cleanText(content);
    if (!plain) {
      return [];
    }

    let sentences = plain.split(/(?<=[.!?])\s+/).map((sentence) => sentence.trim()).filter(Boolean);
    if (sentences.length <= parts) {
      sentences = wordChunkBlocks(plain, parts * 3);
    }

    if (sentences.length <= parts) {
      return [];
    }

    const totalWords = blockWordCount(plain);
    const targetWords = Math.max(90, Math.ceil(totalWords / Math.max(1, parts * 2)));
    const blocks = [];
    let current = [];
    let currentWords = 0;

    sentences.forEach((sentence) => {
      current.push(sentence);
      currentWords += blockWordCount(sentence);

      if (currentWords >= targetWords) {
        blocks.push(current.join(' '));
        current = [];
        currentWords = 0;
      }
    });

    if (current.length) {
      blocks.push(current.join(' '));
    }

    return blocks.filter(Boolean);
  }

  function wordChunkBlocks(plain, targetChunks) {
    const words = String(plain || '').trim().split(/\s+/).filter(Boolean);
    if (words.length < 2) {
      return [];
    }

    const chunkSize = Math.max(90, Math.ceil(words.length / Math.max(1, targetChunks)));
    const chunks = [];
    for (let index = 0; index < words.length; index += chunkSize) {
      chunks.push(words.slice(index, index + chunkSize).join(' '));
    }

    return chunks;
  }

  function expandBlocksForSplit(blocks, content, parts) {
    if (blocks.length > parts) {
      return blocks;
    }

    if (!canUseSentenceSplitFallback(content)) {
      return blocks;
    }

    const expanded = sentenceContentBlocks(content, parts);
    return expanded.length > parts ? expanded : blocks;
  }

  function computeSplitBreaks(blocks, parts, preferred) {
    const total = blocks.length;
    const weights = blocks.map(blockWordCount);
    const totalWords = weights.reduce((sum, weight) => sum + weight, 0);
    const preferredSet = new Set(preferred);
    const minWordsPerPart = Math.max(80, Math.floor((totalWords / parts) * 0.35));
    const breaks = [];
    const used = new Set();

    for (let index = 1; index < parts; index += 1) {
      const targetWords = Math.round((totalWords * index) / parts);
      const previousBreak = breaks.length ? Math.max(...breaks) : 0;
      let chosen = 0;
      let bestScore = Number.POSITIVE_INFINITY;
      let runningWords = 0;

      for (let candidate = 1; candidate < total; candidate += 1) {
        runningWords += weights[candidate - 1];

        if (used.has(candidate) || candidate <= previousBreak) {
          continue;
        }

        const currentPartWords = weights.slice(previousBreak, candidate).reduce((sum, weight) => sum + weight, 0);
        const remainingWords = weights.slice(candidate).reduce((sum, weight) => sum + weight, 0);
        const remainingParts = parts - index;

        if (currentPartWords < minWordsPerPart || remainingWords < minWordsPerPart * remainingParts) {
          continue;
        }

        let score = Math.abs(runningWords - targetWords);
        if (preferredSet.has(candidate)) {
          score = Math.max(0, score - 30);
        }

        if (score < bestScore) {
          bestScore = score;
          chosen = candidate;
        }
      }

      if (!chosen) {
        chosen = fallbackSplitBreak(weights, targetWords, previousBreak, parts - index);
      }

      used.add(chosen);
      breaks.push(chosen);
    }

    return Array.from(new Set(breaks)).sort((a, b) => a - b);
  }

  function fallbackSplitBreak(weights, targetWords, previousBreak, remainingParts) {
    const total = weights.length;
    const minCandidate = Math.max(previousBreak + 1, 1);
    const maxCandidate = Math.max(minCandidate, total - Math.max(1, remainingParts));
    let runningWords = 0;
    let chosen = minCandidate;
    let bestScore = Number.POSITIVE_INFINITY;

    for (let candidate = 1; candidate < total; candidate += 1) {
      runningWords += weights[candidate - 1] || 1;

      if (candidate < minCandidate || candidate > maxCandidate) {
        continue;
      }

      const score = Math.abs(runningWords - targetWords);
      if (score < bestScore) {
        bestScore = score;
        chosen = candidate;
      }
    }

    return Math.max(minCandidate, Math.min(maxCandidate, chosen));
  }

  function splitTextarea(parts) {
    const textarea = getTextarea();
    if (!textarea) {
      return;
    }

    const blocks = expandBlocksForSplit(splitContentBlocks(textarea.value), textarea.value, parts);
    const preferred = preferredTextBreakIndexes(blocks);

    if (blocks.length <= parts) {
      insertAtCursor(textarea, `\n${PAGE_BREAK}\n`);
      return;
    }

    const breaks = computeSplitBreaks(blocks, parts, preferred);

    const output = [];
    blocks.forEach((block, index) => {
      if (breaks.includes(index)) {
        output.push(PAGE_BREAK);
      }
      output.push(block);
    });

    textarea.value = output.join('\n\n');
    textarea.dispatchEvent(new Event('input', { bubbles: true }));
    textarea.focus();
  }

  function addQuicktagsButtons() {
    if (!window.QTags) {
      return;
    }

    window.QTags.addButton('kepoli_nextpage', 'Break', `\n${PAGE_BREAK}\n`, '', 'p', 'Add a page break at the cursor', 121);
    window.QTags.addButton('kepoli_split_two', '2 parts', () => splitTextarea(2), '', '2', 'Split content into two pages', 122);
    window.QTags.addButton('kepoli_split_three', '3 parts', () => splitTextarea(3), '', '3', 'Split content into three pages', 123);
  }

  function recipeTemplate() {
    const ro = [
      '<h2>Pe scurt</h2>',
      '<!-- Scrie 2-3 fraze despre rezultat, ocazie si textura. -->',
      '<h2>Detalii despre reteta</h2>',
      '<p>Timp de pregatire: X minute</p>',
      '<p>Timp de gatire: Y minute</p>',
      '<p>Portii: Z</p>',
      '<p>Nivel: usor/mediu</p>',
      '<h2>Ingrediente</h2>',
      '<!-- Adauga ingredientele intr-o lista, cate unul pe rand. -->',
      '<h2>Mod de preparare</h2>',
      '<!-- Adauga pasii in ordine, cu timp, temperatura si semne vizuale cand este util. -->',
      '<h2>Cum se serveste</h2>',
      '<!-- Spune cu ce merge bine si in ce ocazii se potriveste. -->',
      '<h2>Sfaturi pentru o reteta reusita</h2>',
      '<!-- Noteaza greseli de evitat, ajustari si variante utile. -->',
      '<h2>Variatii ale retetei</h2>',
      '<!-- Adauga 2-3 variante simple, daca se potriveste. -->',
      '<h2>Cum se pastreaza</h2>',
      '<!-- Explica pastrarea, reincalzirea si consumul in siguranta. -->',
      '<h2>Intrebari frecvente</h2>',
      '<h3>Pot pregati reteta in avans?</h3>',
      '<!-- Raspunde practic, cu intervale realiste. -->',
    ].join('\n');

    const en = [
      '<h2>What to know first</h2>',
      '<!-- Write 2-3 sentences about the result, occasion, and texture. -->',
      '<h2>Recipe details</h2>',
      '<p>Prep time: X minutes</p>',
      '<p>Cook time: Y minutes</p>',
      '<p>Servings: Z</p>',
      '<p>Difficulty: easy/medium</p>',
      '<h2>Ingredients</h2>',
      '<!-- Add ingredients in a list, one per line. -->',
      '<h2>Method</h2>',
      '<!-- Add the steps in order, with time, temperature, and visual signs where useful. -->',
      '<h2>How to serve it</h2>',
      '<!-- Explain what to serve it with and when it fits best. -->',
      '<h2>Success notes</h2>',
      '<!-- Note mistakes to avoid, adjustments, and useful variations. -->',
      '<h2>Variations</h2>',
      '<!-- Add 2-3 simple variations when they make sense. -->',
      '<h2>Storage</h2>',
      '<!-- Explain storage, reheating, and safe consumption. -->',
      '<h2>Frequently asked questions</h2>',
      '<h3>Can I prepare this recipe ahead?</h3>',
      '<!-- Answer practically, with realistic time ranges. -->',
    ].join('\n');

    return contentText(ro, en);
  }

  function articleTemplate() {
    const ro = [
      '<h2>Ideea principala</h2>',
      '<!-- Prezinta subiectul si spune cititorului ce va afla. -->',
      '<h2>Ce merita retinut</h2>',
      '<!-- Explica punctele importante in paragrafe scurte, cu exemple concrete. -->',
      '<h2>Cum aplici in bucatarie</h2>',
      '<!-- Leaga sfaturile de retete, ingrediente sau obiceiuri de gatit acasa. -->',
      '<h2>Legaturi utile</h2>',
      '<!-- Adauga linkuri interne catre retete sau ghiduri apropiate. -->',
    ].join('\n');

    const en = [
      '<h2>Main idea</h2>',
      '<!-- Introduce the topic and tell the reader what they will learn. -->',
      '<h2>What to remember</h2>',
      '<!-- Explain the important points in short paragraphs with concrete examples. -->',
      '<h2>How to use it in the kitchen</h2>',
      '<!-- Connect the advice to recipes, ingredients, or home cooking habits. -->',
      '<h2>Useful links</h2>',
      '<!-- Add internal links to nearby recipes or guides. -->',
    ].join('\n');

    return contentText(ro, en);
  }

  function setKind(kind) {
    const input = document.querySelector(`input[name="kepoli_post_kind"][value="${kind}"]`);
    if (input) {
        input.checked = true;
        input.dispatchEvent(new Event('change', { bubbles: true }));
    }
  }

  function bindTemplateButtons() {
    document.querySelectorAll('[data-kepoli-template]').forEach((button) => {
      button.addEventListener('click', () => {
        const type = button.getAttribute('data-kepoli-template');
        if (type === 'recipe') {
          setKind('recipe');
          insertContent(recipeTemplate());
          window.setTimeout(() => {
            completeSetupIfReady('template', true);
            fillRecipeSchema(true);
          }, 120);
        } else {
          setKind('article');
          insertContent(articleTemplate());
          window.setTimeout(() => completeSetupIfReady('template', true), 120);
        }
      });
    });
  }

  function bindKindToggle() {
    const fields = document.querySelector('[data-kepoli-recipe-fields]');
    const inputs = Array.from(document.querySelectorAll('input[name="kepoli_post_kind"]'));

    if (!fields || !inputs.length) {
      return;
    }

    const update = () => {
      const checked = inputs.find((input) => input.checked);
      const showRecipeFields = !!checked && checked.value === 'recipe';
      fields.hidden = !showRecipeFields;
      if (showRecipeFields) {
        fields.open = true;
      }
    };

    inputs.forEach((input) => input.addEventListener('change', update));
    update();
  }

  function bindPassiveAutofill() {
    const titleField = document.getElementById('title');
    const contentField = getTextarea();
    const kindInputs = Array.from(document.querySelectorAll('input[name="kepoli_post_kind"]'));
    const categories = categoryInputs();

    if (titleField) {
      titleField.addEventListener('blur', () => {
        if (oncePerSessionFlag('titleAutofill')) {
          return;
        }

        if (completeSetupIfReady('title', true)) {
          setSessionFlag('titleAutofill', true);
        }
      });
    }

    if (contentField) {
      const triggerContentAutofill = () => {
        if (oncePerSessionFlag('contentAutofill')) {
          return;
        }

        if (completeSetupIfReady('content', true)) {
          setSessionFlag('contentAutofill', true);
        }
      };

      contentField.addEventListener('blur', triggerContentAutofill);
      contentField.addEventListener('paste', () => window.setTimeout(triggerContentAutofill, 180));

      const bindVisualEditorAutofill = () => {
        if (!window.tinymce || !window.tinymce.get) {
          return;
        }

        const editor = window.tinymce.get('content');
        if (!editor || editor.__kepoliPassiveAutofillBound) {
          return;
        }

        editor.__kepoliPassiveAutofillBound = true;
        const schedule = () => window.setTimeout(triggerContentAutofill, 180);

        editor.on('blur', triggerContentAutofill);
        editor.on('change', triggerContentAutofill);
        editor.on('input', schedule);
        editor.on('paste', schedule);
        editor.on('SetContent', schedule);
      };

      bindVisualEditorAutofill();
      window.setTimeout(bindVisualEditorAutofill, 250);

      if (window.tinymce && typeof window.tinymce.on === 'function') {
        window.tinymce.on('AddEditor', (event) => {
          if (event && event.editor && event.editor.id === 'content') {
            window.setTimeout(bindVisualEditorAutofill, 150);
          }
        });
      }
    }

    kindInputs.forEach((input) => {
      input.addEventListener('change', () => {
        if (completeSetupIfReady('kind', false) && input.value === 'recipe') {
          fillRecipeSchema(true);
        }
      });
    });

    categories.forEach((input) => {
      input.addEventListener('change', () => {
        setSessionFlag('categoryManual', true);
      });
    });
  }

  function checklistState() {
    const kind = currentKind();
    const title = currentTitle();
    const content = currentContentText();
    const hasBodyLinks = hasInContentInternalLinks();
    const excerpt = currentFieldValue('textarea[name="kepoli_post_excerpt"]');
    const meta = currentFieldValue('textarea[name="kepoli_meta_description"]');
    const slug = currentSlugValue();
    const relatedRecipes = parseListField('textarea[name="kepoli_related_recipe_slugs"]');
    const relatedArticles = parseListField('textarea[name="kepoli_related_article_slugs"]');
    const imageAlt = currentFieldValue('input[name="kepoli_image_alt"]');
    const recipeIngredients = parseListField('textarea[name="kepoli_recipe_ingredients"]');
    const recipeSteps = parseListField('textarea[name="kepoli_recipe_steps"]');
    const recipeServings = currentFieldValue('input[name="kepoli_recipe_servings"]');
    const recipePrepMinutes = Number.parseInt(currentFieldValue('input[name="kepoli_recipe_prep_minutes"]'), 10) || 0;
    const recipeCookMinutes = Number.parseInt(currentFieldValue('input[name="kepoli_recipe_cook_minutes"]'), 10) || 0;
    const contentLanguage = detectLanguage(`${title} ${excerpt} ${meta} ${content}`);
    const slugLanguage = detectLanguage(slug.replace(/-/g, ' '));
    const servingsMatch = recipeServings.match(/\d+/);
    const recipeServingsValid = recipeServings.trim().length > 0
      && (!servingsMatch || Number.parseInt(servingsMatch[0], 10) > 0);

    return {
      title: title.length >= 6,
      content: content.length >= 320,
      excerpt: excerpt.length >= 20,
      meta: meta.length >= 20,
      language: contentLanguage === 'unknown' || slugLanguage === 'unknown' || contentLanguage === slugLanguage,
      slug: isSlugClean(title, slug),
      featuredImage: hasFeaturedImage(),
      imageAlt: !hasFeaturedImage() ? false : imageAlt.length >= 8,
      related: hasBodyLinks || (relatedRecipes.length + relatedArticles.length) > 0,
      recipe: kind !== 'recipe' || (
        recipeIngredients.length > 0
        && recipeSteps.length > 0
        && recipeServingsValid
        && recipePrepMinutes > 0
        && recipeCookMinutes > 0
      )
    };
  }

  function missingChecklistLabels(state) {
    const labels = {
      title: 'title',
      content: 'content',
      excerpt: 'excerpt',
      meta: 'meta description',
      language: 'language',
      slug: 'slug',
      featuredImage: 'featured image',
      imageAlt: 'alt text',
      related: 'internal links',
      recipe: 'recipe schema'
    };

    return Object.keys(state)
      .filter((key) => !state[key])
      .map((key) => labels[key]);
  }

  function renderChecklist() {
    const checklist = document.querySelector('[data-kepoli-editor-checklist]');
    const summary = document.querySelector('[data-kepoli-checklist-summary]');
    if (!checklist || !summary) {
      return;
    }

    const kind = currentKind();
    const state = checklistState();
    const items = checklist.querySelectorAll('[data-kepoli-check]');

    items.forEach((item) => {
      const key = item.getAttribute('data-kepoli-check');
      const done = !!state[key];
      const isRecipeOnly = key === 'recipe';

      item.hidden = isRecipeOnly && kind !== 'recipe';
      item.classList.toggle('is-done', done);
      item.classList.toggle('is-missing', !done);
    });

    const missing = missingChecklistLabels(state).filter((label) => !(kind !== 'recipe' && label === 'recipe schema'));
    const strings = (window.kepoliAuthorTools && window.kepoliAuthorTools.strings) || {};

    if (!missing.length) {
      summary.textContent = strings.checkReady || 'Setup is almost complete.';
      summary.classList.add('is-ready');
      summary.classList.remove('is-missing');
      return;
    }

    summary.textContent = `${strings.checkMissingPrefix || 'Complete before publishing:'} ${missing.join(', ')}.`;
    summary.classList.add('is-missing');
    summary.classList.remove('is-ready');
  }

  function renderPublishCompanion() {
    const categoryTarget = document.querySelector('[data-kepoli-companion-category]');
    const tagsTarget = document.querySelector('[data-kepoli-companion-tags]');
    const checksTarget = document.querySelector('[data-kepoli-companion-checks]');
    const summaryTarget = document.querySelector('[data-kepoli-companion-summary]');
    const statusTarget = document.querySelector('[data-kepoli-companion-status]');

    if (!categoryTarget || !tagsTarget || !checksTarget || !summaryTarget || !statusTarget) {
      return;
    }

    const strings = (window.kepoliAuthorTools && window.kepoliAuthorTools.strings) || {};
    const category = suggestedCategory();
    const tags = suggestedTags();
    const state = checklistState();
    const missing = missingChecklistLabels(state).filter((label) => !(currentKind() !== 'recipe' && label === 'recipe schema'));

    categoryTarget.textContent = category ? category.name : (strings.companionNoCategory || 'No clear suggestion yet');
    tagsTarget.textContent = tags.length ? tags.join(', ') : (strings.companionNoTags || 'No suggested tags yet');

    checksTarget.innerHTML = '';
    if (missing.length) {
      missing.forEach((item) => {
        const li = document.createElement('li');
        li.textContent = item;
        checksTarget.appendChild(li);
      });
    } else {
      const li = document.createElement('li');
      li.textContent = 'final read only';
      checksTarget.appendChild(li);
    }

    if (!missing.length) {
      statusTarget.textContent = strings.companionStatusReady || 'Ready for a final read.';
    } else if (missing.length === 1) {
      statusTarget.textContent = strings.companionStatusSingle || '1 important item is still missing.';
    } else {
      const template = strings.companionStatusMultiple || '%d important items are still missing.';
      statusTarget.textContent = template.replace('%d', String(missing.length));
    }

    summaryTarget.textContent = missing.length
      ? (strings.companionReview || 'A few things still need review before publishing.')
      : (strings.companionReady || 'The post looks ready for the next step.');
    summaryTarget.classList.toggle('is-ready', !missing.length);
    summaryTarget.classList.toggle('is-missing', missing.length > 0);
  }

  function bindChecklist() {
    const fields = document.querySelectorAll(
      '#title, #content, input[name="kepoli_post_kind"], textarea[name="kepoli_post_excerpt"], textarea[name="kepoli_meta_description"], textarea[name="kepoli_related_recipe_slugs"], textarea[name="kepoli_related_article_slugs"], input[name="kepoli_image_alt"], input[name="kepoli_recipe_servings"], input[name="kepoli_recipe_prep_minutes"], input[name="kepoli_recipe_cook_minutes"], input[name="kepoli_recipe_total_minutes"], textarea[name="kepoli_recipe_ingredients"], textarea[name="kepoli_recipe_steps"], #_thumbnail_id'
    );

    if (!fields.length) {
      return;
    }

    fields.forEach((field) => {
      field.addEventListener('input', renderChecklist);
      field.addEventListener('change', renderChecklist);
    });

    document.addEventListener('click', (event) => {
      const target = event.target;
      if (!(target instanceof HTMLElement)) {
        return;
      }

      if (target.closest('#set-post-thumbnail, .editor-post-featured-image, .thickbox')) {
        window.setTimeout(() => {
          renderChecklist();
          renderPublishCompanion();
        }, 250);
      }
    });

    renderChecklist();
    renderPublishCompanion();
  }

  function bindPublishWarning() {
    const publishButton = document.getElementById('publish');
    if (!publishButton) {
      return;
    }

    publishButton.addEventListener('click', (event) => {
      const state = checklistState();
      const missing = missingChecklistLabels(state).filter((label) => !(currentKind() !== 'recipe' && label === 'recipe schema'));
      if (!missing.length) {
        return;
      }

      const strings = (window.kepoliAuthorTools && window.kepoliAuthorTools.strings) || {};
      const message = `${strings.publishConfirmPrefix || 'The post still has missing fields:'} ${missing.join(', ')}.\n\n${strings.publishConfirmSuffix || 'Publish anyway?'}`;
      if (!window.confirm(message)) {
        event.preventDefault();
      }
    });
  }

  function bindCompanionRefresh() {
    const fields = document.querySelectorAll(
      '#title, #content, input[name="kepoli_post_kind"], textarea[name="kepoli_post_excerpt"], textarea[name="kepoli_meta_description"], textarea[name="kepoli_related_recipe_slugs"], textarea[name="kepoli_related_article_slugs"], input[name="kepoli_image_alt"], input[name="kepoli_recipe_servings"], input[name="kepoli_recipe_prep_minutes"], input[name="kepoli_recipe_cook_minutes"], input[name="kepoli_recipe_total_minutes"], textarea[name="kepoli_recipe_ingredients"], textarea[name="kepoli_recipe_steps"], #_thumbnail_id'
    );

    fields.forEach((field) => {
      field.addEventListener('input', renderPublishCompanion);
      field.addEventListener('change', renderPublishCompanion);
    });

    categoryInputs().forEach((input) => input.addEventListener('change', renderPublishCompanion));
    const tags = tagField();
    if (tags) {
      tags.addEventListener('input', renderPublishCompanion);
      tags.addEventListener('change', renderPublishCompanion);
    }

    renderPublishCompanion();
  }

  function bindCompanionActions() {
    const button = document.querySelector('[data-kepoli-companion-complete]');
    if (!button) {
      return;
    }

    button.addEventListener('click', () => {
      completeSetup();
      setStatus('Final empty fields were filled automatically. Review the result before publishing.');
      renderChecklist();
      renderPublishCompanion();
    });
  }

  function initKepoliAuthorTools() {
    addQuicktagsButtons();
    bindAutomationButtons();
    bindTemplateButtons();
    bindKindToggle();
    bindPassiveAutofill();
    bindChecklist();
    bindCompanionRefresh();
    bindCompanionActions();
    bindPublishWarning();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initKepoliAuthorTools);
  } else {
    initKepoliAuthorTools();
  }
})();
