(function () {
  const PAGE_BREAK = '<!--nextpage-->';

  function nodeToHtml(node) {
    const container = document.createElement('div');
    container.appendChild(node.cloneNode(true));
    return container.innerHTML;
  }

  function getCleanNodes(html) {
    const container = document.createElement('div');
    container.innerHTML = html.replace(/<!--\s*nextpage\s*-->/gi, '');

    return Array.from(container.childNodes).filter((node) => {
      if (node.nodeType === Node.TEXT_NODE) {
        return node.textContent.trim() !== '';
      }

      if (node.nodeType === Node.COMMENT_NODE) {
        return false;
      }

      return true;
    });
  }

  function insertPageBreak(editor) {
    editor.insertContent(`\n${PAGE_BREAK}\n`);
    editor.nodeChanged();
  }

  function splitEditorContent(editor, parts) {
    const nodes = getCleanNodes(editor.getContent({ format: 'html' }));

    if (nodes.length <= parts) {
      insertPageBreak(editor);
      return;
    }

    const breaks = [];
    for (let index = 1; index < parts; index += 1) {
      breaks.push(Math.max(1, Math.round((nodes.length * index) / parts)));
    }

    const output = [];
    nodes.forEach((node, index) => {
      if (breaks.includes(index)) {
        output.push(PAGE_BREAK);
      }
      output.push(nodeToHtml(node));
    });

    editor.undoManager.transact(() => {
      editor.setContent(output.join('\n'));
    });
    editor.nodeChanged();
    editor.fire('change');
  }

  tinymce.PluginManager.add('kepoli_author_tools', (editor) => {
    editor.addButton('kepoli_page_break', {
      text: 'Pauza',
      tooltip: 'Adauga pauza de pagina la cursor',
      onclick: () => insertPageBreak(editor),
    });

    editor.addButton('kepoli_split_two', {
      text: '2 parti',
      tooltip: 'Imparte continutul in doua pagini',
      onclick: () => splitEditorContent(editor, 2),
    });

    editor.addButton('kepoli_split_three', {
      text: '3 parti',
      tooltip: 'Imparte continutul in trei pagini',
      onclick: () => splitEditorContent(editor, 3),
    });
  });
})();
