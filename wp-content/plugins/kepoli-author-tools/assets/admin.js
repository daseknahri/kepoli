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

  function setStatus(message) {
    const status = document.querySelector('[data-kepoli-automation-status]');
    if (!status) {
      return;
    }

    status.textContent = message;
    window.setTimeout(() => {
      status.textContent = '';
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

  function scorePost(post, sourceWords) {
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

    return score;
  }

  function relatedSuggestions(kind) {
    const posts = (window.kepoliAuthorTools && window.kepoliAuthorTools.relatedPosts) || [];
    const sourceWords = normalizeWords(`${currentTitle()} ${currentContentText()}`);

    return posts
      .map((post) => ({ ...post, score: scorePost(post, sourceWords) }))
      .filter((post) => post.slug && post.kind === kind)
      .sort((a, b) => b.score - a.score || a.title.localeCompare(b.title));
  }

  function bindAutomationButtons() {
    const excerptButton = document.querySelector('[data-kepoli-generate-excerpt]');
    const metaButton = document.querySelector('[data-kepoli-generate-meta]');
    const relatedButton = document.querySelector('[data-kepoli-suggest-related]');
    const imageButton = document.querySelector('[data-kepoli-generate-image-meta]');

    if (excerptButton) {
      excerptButton.addEventListener('click', () => {
        const text = currentContentText();
        const title = currentTitle();
        const excerpt = shortSentence(text || title, 220);
        setField('textarea[name="kepoli_post_excerpt"]', excerpt);
        setStatus('Excerpt generat. Ajusteaza-l daca vrei un rezumat mai editorial.');
      });
    }

    if (metaButton) {
      metaButton.addEventListener('click', () => {
        const text = currentContentText();
        const title = currentTitle();
        const description = shortSentence(text || title, 155);
        setField('textarea[name="kepoli_meta_description"]', description);
        setStatus('Meta description generata. Verifica textul inainte de publicare.');
      });
    }

    if (relatedButton) {
      relatedButton.addEventListener('click', () => {
        const kind = currentKind();
        const recipes = relatedSuggestions('recipe').slice(0, kind === 'recipe' ? 3 : 5).map((post) => post.slug);
        const articles = relatedSuggestions('article').slice(0, kind === 'recipe' ? 1 : 2).map((post) => post.slug);

        setField('textarea[name="kepoli_related_recipe_slugs"]', recipes.join(', '));
        setField('textarea[name="kepoli_related_article_slugs"]', articles.join(', '));
        setStatus('Linkuri interne sugerate. Ajusteaza lista daca vrei alte recomandari.');
      });
    }

    if (imageButton) {
      imageButton.addEventListener('click', () => {
        const title = currentTitle() || 'Reteta Kepoli';
        const kind = currentKind();
        const prefix = kind === 'article' ? 'Imagine editoriala pentru' : 'Fotografie culinara pentru';
        const alt = shortSentence(`${prefix} ${title}, publicata pe blogul romanesc Kepoli.`, 150);
        const caption = shortSentence(`${title} pe Kepoli.`, 120);
        const description = shortSentence(`Imagine reprezentativa pentru ${title}, folosita in articolul culinar Kepoli.`, 220);

        setField('input[name="kepoli_image_alt"]', alt);
        setField('input[name="kepoli_image_title"]', title);
        setField('input[name="kepoli_image_caption"]', caption);
        setField('textarea[name="kepoli_image_description"]', description);
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
        } else {
          setKind('article');
          insertContent(articleTemplate());
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
      fields.hidden = !checked || checked.value !== 'recipe';
    };

    inputs.forEach((input) => input.addEventListener('change', update));
    update();
  }

  function initKepoliAuthorTools() {
    addQuicktagsButtons();
    bindAutomationButtons();
    bindTemplateButtons();
    bindKindToggle();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initKepoliAuthorTools);
  } else {
    initKepoliAuthorTools();
  }
})();
