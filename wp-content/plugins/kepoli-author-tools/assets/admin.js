(function () {
  const PAGE_BREAK = '<!--nextpage-->';

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

  function currentFieldValue(selector) {
    const field = document.querySelector(selector);
    return field ? String(field.value || '').trim() : '';
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
    if (!field || String(field.value || '').trim()) {
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
      } else if (category !== 'articole' && normalizedPostCategories.length) {
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
    return title ? shortSentence(title, 65).replace(/\.\.\.$/, '') : '';
  }

  function generatedExcerpt() {
    const text = currentContentText();
    const title = currentTitle();
    return shortSentence(text || title, 220);
  }

  function generatedMetaDescription() {
    const text = currentContentText();
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
    const title = currentTitle() || 'Reteta Kepoli';
    const kind = currentKind();
    const prefix = kind === 'article' ? 'Imagine editoriala pentru' : 'Fotografie culinara pentru';

    return {
      alt: shortSentence(`${prefix} ${title}, publicata pe blogul romanesc Kepoli.`, 150),
      title: title,
      caption: shortSentence(`${title} pe Kepoli.`, 120),
      description: shortSentence(`Imagine reprezentativa pentru ${title}, folosita in articolul culinar Kepoli.`, 220)
    };
  }

  function dedupeTags(tags) {
    const seen = new Set();
    return tags.filter((tag) => {
      const key = tag.toLowerCase();
      if (!tag || seen.has(key)) {
        return false;
      }

      seen.add(key);
      return true;
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
      ? ['ingrediente', 'organizare', 'tehnici']
      : ['retete romanesti'];

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
      papanasi: ['papanasi', 'desert'],
      placinta: ['placinta', 'desert'],
      cozonac: ['cozonac', 'aluat'],
      zacusca: ['zacusca', 'conserve'],
      muraturi: ['muraturi', 'conserve'],
      ghid: ['ingrediente'],
      meniu: ['meniu', 'familie'],
      aluat: ['aluat', 'patiserie'],
      sezon: ['sezon'],
      pastrare: ['pastrare', 'organizare']
    };

    Object.keys(quickTagMap).forEach((keyword) => {
      if (titleText.includes(keyword)) {
        quickTagMap[keyword].forEach((tag) => {
          tagScores.set(tag, (tagScores.get(tag) || 0) + 5);
        });
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

    if (!field || !tags.length) {
      return tags;
    }

    if (!force && currentTags().length > 0) {
      return tags;
    }

    field.value = tags.join(', ');
    field.dispatchEvent(new Event('input', { bubbles: true }));
    field.dispatchEvent(new Event('change', { bubbles: true }));
    return tags;
  }

  function suggestedCategory() {
    const categories = (window.kepoliAuthorTools && window.kepoliAuthorTools.categories) || [];
    const text = `${currentTitle()} ${currentContentText()}`;
    const titleWords = normalizeWords(currentTitle());
    const sourceWords = normalizeWords(text);
    const posts = (window.kepoliAuthorTools && window.kepoliAuthorTools.relatedPosts) || [];
    const categoryScores = new Map();
    const slugKeywords = {
      'ciorbe-si-supe': ['ciorba', 'bors', 'supa', 'supa crema', 'zeama', 'galuste', 'galuste', 'radauteana'],
      'feluri-principale': ['sarmale', 'tochitura', 'tocanita', 'friptura', 'mamaliga', 'ostropel', 'snitel', 'varza', 'pilaf', 'chiftele'],
      'patiserie-si-deserturi': ['desert', 'prajitura', 'cozonac', 'placinta', 'clatite', 'papanasi', 'chec', 'cornulete', 'aluat', 'foi'],
      'conserve-si-garnituri': ['zacusca', 'muraturi', 'salata', 'garnitura', 'borcan', 'compot', 'bulion', 'gem', 'dulceata', 'piure'],
      'articole': ['ghid', 'cum', 'calendar', 'meniuri', 'tehnici', 'organizare', 'ingrediente', 'bucatarie', 'pastrare', 'explica']
    };

    categories.forEach((category) => {
      let score = 0;
      const haystack = normalizeWords([category.name, category.description].join(' '));
      const keywords = slugKeywords[category.slug] || [];

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

      if (currentKind() === 'article' && category.slug === 'articole') {
        score += 12;
      }

      if (currentKind() === 'recipe' && category.slug === 'articole') {
        score -= 10;
      }

      posts.forEach((post) => {
        const postWords = normalizeWords([post.title, post.excerpt, (post.tags || []).join(' ')].join(' '));
        const overlap = titleWords.filter((word) => postWords.includes(word)).length;
        if (!overlap) {
          return;
        }

        (post.categories || []).forEach((categoryName) => {
          const matchingCategory = categories.find((item) => item.name === categoryName);
          if (matchingCategory && matchingCategory.id === category.id) {
            score += overlap * 2;
          }
        });
      });

      categoryScores.set(category.id, score);
    });

    return categories
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
    const html = currentContentHtml();
    if (!html) {
      return [];
    }

    const container = document.createElement('div');
    container.innerHTML = html;

    const sectionHeadings = {
      ingredients: ['ingrediente'],
      steps: ['mod de preparare', 'preparare', 'pasi', 'pași']
    };

    const targetHeadings = sectionHeadings[sectionName] || [];
    const sectionItems = [];
    let active = false;

    Array.from(container.childNodes).forEach((node) => {
      if (node.nodeType !== Node.ELEMENT_NODE) {
        return;
      }

      const element = node;
      const tag = element.tagName.toLowerCase();

      if (/^h[1-6]$/.test(tag)) {
        const heading = normalizedHeading(element.textContent || '');
        active = targetHeadings.some((candidate) => heading === normalizedHeading(candidate));
        return;
      }

      if (!active) {
        return;
      }

      if (/^h[1-6]$/.test(tag)) {
        active = false;
        return;
      }

      if (tag === 'ul' || tag === 'ol') {
        Array.from(element.querySelectorAll('li')).forEach((item) => {
          const text = cleanText(item.textContent || '');
          if (text) {
            sectionItems.push(text);
          }
        });
        return;
      }

      const text = cleanText(element.textContent || '');
      if (!text) {
        return;
      }

      if (sectionName === 'ingredients') {
        text.split(/\s*[,;\n]\s*/).map((part) => part.trim()).filter(Boolean).forEach((part) => sectionItems.push(part));
        return;
      }

      if (sectionName === 'steps') {
        sectionItems.push(text);
      }
    });

    return Array.from(new Set(sectionItems));
  }

  function extractRecipeMetaFromText() {
    const text = currentContentText();
    const servingsMatch = text.match(/(?:pentru|aproximativ|cam)?\s*(\d{1,2}\s*(?:portii|porții|persoane))/i);
    const prepMatch = text.match(/(?:pregatire|preparare)\s*:?\s*(\d{1,3})\s*(?:min|minute)/i);
    const cookMatch = text.match(/(?:gatire|coacere|fierbere)\s*:?\s*(\d{1,3})\s*(?:min|minute)/i);

    return {
      servings: servingsMatch ? servingsMatch[1] : '',
      prepMinutes: prepMatch ? prepMatch[1] : '',
      cookMinutes: cookMatch ? cookMatch[1] : '',
      ingredients: extractRecipeSection('ingredients'),
      steps: extractRecipeSection('steps')
    };
  }

  function fillRecipeSchema(extractOnlyIfEmpty) {
    const data = extractRecipeMetaFromText();
    const setter = extractOnlyIfEmpty ? setFieldIfEmpty : setField;

    setter('input[name="kepoli_recipe_servings"]', data.servings);
    setter('input[name="kepoli_recipe_prep_minutes"]', data.prepMinutes);
    setter('input[name="kepoli_recipe_cook_minutes"]', data.cookMinutes);
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
      setStatus('Kepoli a completat automat campurile goale pe baza titlului si continutului curent.');
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
        setStatus('Campurile goale au fost completate automat. Verifica rezultatul inainte de publicare.');
      });
    }

    if (categoryButton) {
      categoryButton.addEventListener('click', () => {
        const suggestion = applySuggestedCategory(true);
        setStatus(
          suggestion
            ? `Categoria sugerata a fost selectata: ${suggestion.name}.`
            : 'Nu am gasit o categorie suficient de clara in continut.'
        );
      });
    }

    if (tagsButton) {
      tagsButton.addEventListener('click', () => {
        const tags = applySuggestedTags(true);
        setStatus(
          tags.length
            ? `Tagurile sugerate au fost completate: ${tags.join(', ')}.`
            : 'Nu am gasit destule repere pentru taguri clare.'
        );
      });
    }

    if (recipeButton) {
      recipeButton.addEventListener('click', () => {
        const data = fillRecipeSchema(false);
        const hasData = data.ingredients.length || data.steps.length || data.servings || data.prepMinutes || data.cookMinutes;
        setStatus(
          hasData
            ? 'Schema retetei a fost extrasa din continut. Verifica ingredientele, pasii si timpii.'
            : 'Nu am gasit destule repere in continut. Foloseste titlurile Ingrediente si Mod de preparare sau completeaza manual.'
        );
      });
    }

    if (excerptButton) {
      excerptButton.addEventListener('click', () => {
        setField('textarea[name="kepoli_post_excerpt"]', generatedExcerpt());
        setStatus('Excerpt generat. Ajusteaza-l daca vrei un rezumat mai editorial.');
      });
    }

    if (metaButton) {
      metaButton.addEventListener('click', () => {
        setField('textarea[name="kepoli_meta_description"]', generatedMetaDescription());
        setStatus('Meta description generata. Verifica textul inainte de publicare.');
      });
    }

    if (relatedButton) {
      relatedButton.addEventListener('click', () => {
        const related = generatedRelated();

        setField('textarea[name="kepoli_related_recipe_slugs"]', related.recipes.join(', '));
        setField('textarea[name="kepoli_related_article_slugs"]', related.articles.join(', '));
        setStatus('Linkuri interne sugerate. Ajusteaza lista daca vrei alte recomandari.');
      });
    }

    if (imageButton) {
      imageButton.addEventListener('click', () => {
        const imageMeta = generatedImageMeta();

        setField('input[name="kepoli_image_alt"]', imageMeta.alt);
        setField('input[name="kepoli_image_title"]', imageMeta.title);
        setField('input[name="kepoli_image_caption"]', imageMeta.caption);
        setField('textarea[name="kepoli_image_description"]', imageMeta.description);
        setStatus('Image meta generata. Verifica daca descrie corect imaginea aleasa.');
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

  function splitTextarea(parts) {
    const textarea = getTextarea();
    if (!textarea) {
      return;
    }

    const clean = textarea.value.replace(/<!--\s*nextpage\s*-->/gi, '').trim();
    const blocks = clean.split(/\n{2,}/).map((block) => block.trim()).filter(Boolean);

    if (blocks.length <= parts) {
      insertAtCursor(textarea, `\n${PAGE_BREAK}\n`);
      return;
    }

    const breaks = [];
    for (let index = 1; index < parts; index += 1) {
      breaks.push(Math.max(1, Math.round((blocks.length * index) / parts)));
    }

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

    window.QTags.addButton('kepoli_nextpage', 'Pauza', `\n${PAGE_BREAK}\n`, '', 'p', 'Adauga pauza de pagina', 121);
    window.QTags.addButton('kepoli_split_two', '2 parti', () => splitTextarea(2), '', '2', 'Imparte continutul in doua pagini', 122);
    window.QTags.addButton('kepoli_split_three', '3 parti', () => splitTextarea(3), '', '3', 'Imparte continutul in trei pagini', 123);
  }

  function recipeTemplate() {
    return [
      '<h2>Pe scurt</h2>',
      '<!-- Kepoli: scrie 2-3 fraze despre rezultat, ocazie si textura. -->',
      '<h2>Ingrediente</h2>',
      '<!-- Kepoli: adauga ingredientele intr-o lista, cate unul pe rand. -->',
      '<h2>Mod de preparare</h2>',
      '<!-- Kepoli: adauga pasii in ordine, cu timp, temperatura si semne vizuale cand este util. -->',
      '<h2>Sfaturi pentru reusita</h2>',
      '<!-- Kepoli: noteaza greseli de evitat, ajustari si variante utile. -->',
      '<h2>Cum pastrezi</h2>',
      '<!-- Kepoli: explica pastrarea, reincalzirea si consumul in siguranta. -->',
      '<h2>Intrebari frecvente</h2>',
      '<h3>Pot pregati reteta in avans?</h3>',
      '<!-- Kepoli: raspunde practic, cu intervale realiste. -->',
    ].join('\n');
  }

  function articleTemplate() {
    return [
      '<h2>Ideea principala</h2>',
      '<!-- Kepoli: prezinta subiectul si spune cititorului ce va afla. -->',
      '<h2>Ce merita retinut</h2>',
      '<!-- Kepoli: explica punctele importante in paragrafe scurte, cu exemple concrete. -->',
      '<h2>Cum aplici in bucatarie</h2>',
      '<!-- Kepoli: leaga sfaturile de retete, ingrediente sau obiceiuri de gatit acasa. -->',
      '<h2>Legaturi utile</h2>',
      '<!-- Kepoli: adauga linkuri interne catre retete sau ghiduri apropiate. -->',
    ].join('\n');
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
    const relatedRecipes = parseListField('textarea[name="kepoli_related_recipe_slugs"]');
    const relatedArticles = parseListField('textarea[name="kepoli_related_article_slugs"]');
    const imageAlt = currentFieldValue('input[name="kepoli_image_alt"]');
    const recipeIngredients = parseListField('textarea[name="kepoli_recipe_ingredients"]');
    const recipeSteps = parseListField('textarea[name="kepoli_recipe_steps"]');
    const recipeServings = currentFieldValue('input[name="kepoli_recipe_servings"]');

    return {
      title: title.length >= 6,
      content: content.length >= 320,
      excerpt: excerpt.length >= 20,
      meta: meta.length >= 20,
      featuredImage: hasFeaturedImage(),
      imageAlt: !hasFeaturedImage() ? false : imageAlt.length >= 8,
      related: hasBodyLinks || (relatedRecipes.length + relatedArticles.length) > 0,
      recipe: kind !== 'recipe' || (recipeIngredients.length > 0 && recipeSteps.length > 0 && recipeServings.length > 0)
    };
  }

  function missingChecklistLabels(state) {
    const labels = {
      title: 'titlu',
      content: 'continut',
      excerpt: 'excerpt',
      meta: 'meta description',
      featuredImage: 'imagine',
      imageAlt: 'alt text',
      related: 'linkuri interne',
      recipe: 'schema reteta'
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

    const missing = missingChecklistLabels(state).filter((label) => !(kind !== 'recipe' && label === 'schema reteta'));
    const strings = (window.kepoliAuthorTools && window.kepoliAuthorTools.strings) || {};

    if (!missing.length) {
      summary.textContent = strings.checkReady || 'Setup aproape complet.';
      summary.classList.add('is-ready');
      summary.classList.remove('is-missing');
      return;
    }

    summary.textContent = `${strings.checkMissingPrefix || 'De completat:'} ${missing.join(', ')}.`;
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
    const missing = missingChecklistLabels(state).filter((label) => !(currentKind() !== 'recipe' && label === 'schema reteta'));

    categoryTarget.textContent = category ? category.name : (strings.companionNoCategory || 'Nicio sugestie clara inca');
    tagsTarget.textContent = tags.length ? tags.join(', ') : (strings.companionNoTags || 'Fara taguri sugerate inca');

    checksTarget.innerHTML = '';
    if (missing.length) {
      missing.forEach((item) => {
        const li = document.createElement('li');
        li.textContent = item;
        checksTarget.appendChild(li);
      });
    } else {
      const li = document.createElement('li');
      li.textContent = 'doar lectura finala';
      checksTarget.appendChild(li);
    }

    if (!missing.length) {
      statusTarget.textContent = strings.companionStatusReady || 'Gata pentru o ultima lectura.';
    } else if (missing.length === 1) {
      statusTarget.textContent = strings.companionStatusSingle || 'Mai lipseste 1 lucru important.';
    } else {
      const template = strings.companionStatusMultiple || 'Mai lipsesc %d lucruri importante.';
      statusTarget.textContent = template.replace('%d', String(missing.length));
    }

    summaryTarget.textContent = missing.length
      ? (strings.companionReview || 'Mai sunt cateva lucruri de verificat inainte sa publici.')
      : (strings.companionReady || 'Postarea arata bine pentru urmatorul pas.');
    summaryTarget.classList.toggle('is-ready', !missing.length);
    summaryTarget.classList.toggle('is-missing', missing.length > 0);
  }

  function bindChecklist() {
    const fields = document.querySelectorAll(
      '#title, #content, input[name="kepoli_post_kind"], textarea[name="kepoli_post_excerpt"], textarea[name="kepoli_meta_description"], textarea[name="kepoli_related_recipe_slugs"], textarea[name="kepoli_related_article_slugs"], input[name="kepoli_image_alt"], input[name="kepoli_recipe_servings"], textarea[name="kepoli_recipe_ingredients"], textarea[name="kepoli_recipe_steps"], #_thumbnail_id'
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
      const missing = missingChecklistLabels(state).filter((label) => !(currentKind() !== 'recipe' && label === 'schema reteta'));
      if (!missing.length) {
        return;
      }

      const strings = (window.kepoliAuthorTools && window.kepoliAuthorTools.strings) || {};
      const message = `${strings.publishConfirmPrefix || 'Postarea mai are campuri lipsa:'} ${missing.join(', ')}.\n\n${strings.publishConfirmSuffix || 'Continui publicarea?'}`;
      if (!window.confirm(message)) {
        event.preventDefault();
      }
    });
  }

  function bindCompanionRefresh() {
    const fields = document.querySelectorAll(
      '#title, #content, input[name="kepoli_post_kind"], textarea[name="kepoli_post_excerpt"], textarea[name="kepoli_meta_description"], textarea[name="kepoli_related_recipe_slugs"], textarea[name="kepoli_related_article_slugs"], input[name="kepoli_image_alt"], input[name="kepoli_recipe_servings"], textarea[name="kepoli_recipe_ingredients"], textarea[name="kepoli_recipe_steps"], #_thumbnail_id'
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
      setStatus('Kepoli a facut completarea finala a campurilor goale. Verifica rezultatul inainte de publicare.');
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
