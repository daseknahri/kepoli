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
      '<p>Scrie aici de ce merita pregatita reteta, cand se potriveste si ce rezultat trebuie sa obtina cititorul.</p>',
      '<h2>Ingrediente</h2>',
      '<ul>',
      '<li>Ingredient 1</li>',
      '<li>Ingredient 2</li>',
      '<li>Ingredient 3</li>',
      '</ul>',
      '<h2>Mod de preparare</h2>',
      '<ol>',
      '<li>Descrie primul pas clar, cu temperatura, timp sau semne vizuale daca este nevoie.</li>',
      '<li>Continua cu pasii in ordinea fireasca.</li>',
      '<li>Incheie cu momentul in care preparatul este gata.</li>',
      '</ol>',
      '<h2>Sfaturi pentru reusita</h2>',
      '<p>Adauga ajustari, greseli de evitat si variante utile pentru ingrediente.</p>',
      '<h2>Cum pastrezi</h2>',
      '<p>Explica pastrarea la frigider, reincalzirea sau consumul in siguranta.</p>',
      '<h2>Intrebari frecvente</h2>',
      '<h3>Pot pregati reteta in avans?</h3>',
      '<p>Raspunde practic, cu intervale realiste.</p>',
    ].join('\n');
  }

  function articleTemplate() {
    return [
      '<h2>Ideea principala</h2>',
      '<p>Prezinta subiectul si spune cititorului ce va invata din articol.</p>',
      '<h2>Ce merita retinut</h2>',
      '<p>Explica punctele importante in paragrafe scurte, cu exemple concrete.</p>',
      '<h2>Cum aplici in bucatarie</h2>',
      '<p>Leaga sfaturile de retete, ingrediente sau obiceiuri de gatit acasa.</p>',
      '<h2>Legaturi utile</h2>',
      '<p>Adauga linkuri interne catre retete sau ghiduri Kepoli apropiate.</p>',
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
    bindTemplateButtons();
    bindKindToggle();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initKepoliAuthorTools);
  } else {
    initKepoliAuthorTools();
  }
})();
