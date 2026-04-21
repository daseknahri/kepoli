(function () {
  const PAGE_BREAK = '<!--nextpage-->';

  function getTextarea() {
    return document.getElementById('content');
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

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', addQuicktagsButtons);
  } else {
    addQuicktagsButtons();
  }
})();
