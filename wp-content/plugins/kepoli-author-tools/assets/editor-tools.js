(function () {
  var PAGE_BREAK = '<!--nextpage-->';

  function nodeToHtml(node) {
    var container = document.createElement('div');
    container.appendChild(node.cloneNode(true));
    return container.innerHTML;
  }

  function getCleanNodes(html) {
    var container = document.createElement('div');
    var nodes;

    container.innerHTML = String(html || '').replace(/<!--\s*nextpage\s*-->/gi, '');
    nodes = Array.prototype.slice.call(container.childNodes);

    return nodes.filter(function (node) {
      if (node.nodeType === 3) {
        return node.textContent.trim() !== '';
      }

      if (node.nodeType === 8) {
        return false;
      }

      return true;
    });
  }

  function insertPageBreak(editor) {
    editor.insertContent('\n' + PAGE_BREAK + '\n');
    editor.nodeChanged();
  }

  function isHeadingNode(node) {
    return node && node.nodeType === 1 && /^H[1-6]$/.test(node.tagName || '');
  }

  function preferredBreakIndexes(nodes) {
    return nodes.reduce(function (indexes, node, index) {
      if (!isHeadingNode(node) || index === 0) {
        return indexes;
      }

      if (/^H[23]$/.test(node.tagName || '')) {
        indexes.push(index);
      }

      return indexes;
    }, []);
  }

  function nodeWordCount(node) {
    var plain = node && node.textContent ? node.textContent.replace(/\s+/g, ' ').trim() : '';
    return Math.max(1, plain ? plain.split(/\s+/).length : 1);
  }

  function computeBreaks(nodes, parts, preferred) {
    var total = nodes.length;
    var weights = nodes.map(nodeWordCount);
    var totalWords = weights.reduce(function (sum, weight) { return sum + weight; }, 0);
    var preferredSet = {};
    var minWordsPerPart = Math.max(80, Math.floor((totalWords / parts) * 0.35));
    var breaks = [];
    var used = {};
    var index;

    preferred.forEach(function (candidate) {
      preferredSet[candidate] = true;
    });

    for (index = 1; index < parts; index += 1) {
      var targetWords = Math.round((totalWords * index) / parts);
      var previousBreak = breaks.length ? Math.max.apply(null, breaks) : 0;
      var chosen = 0;
      var bestScore = Number.POSITIVE_INFINITY;
      var runningWords = 0;
      var candidate;

      for (candidate = 1; candidate < total; candidate += 1) {
        var currentPartWords;
        var remainingWords;
        var remainingParts;
        var score;

        runningWords += weights[candidate - 1];

        if (used[candidate] || candidate <= previousBreak) {
          continue;
        }

        currentPartWords = weights.slice(previousBreak, candidate).reduce(function (sum, weight) { return sum + weight; }, 0);
        remainingWords = weights.slice(candidate).reduce(function (sum, weight) { return sum + weight; }, 0);
        remainingParts = parts - index;

        if (currentPartWords < minWordsPerPart || remainingWords < minWordsPerPart * remainingParts) {
          continue;
        }

        score = Math.abs(runningWords - targetWords);
        if (preferredSet[candidate]) {
          score = Math.max(0, score - 30);
        }

        if (score < bestScore) {
          bestScore = score;
          chosen = candidate;
        }
      }

      if (!chosen) {
        chosen = Math.max(1, Math.min(total - 1, Math.round((total * index) / parts)));
      }

      used[chosen] = true;
      breaks.push(chosen);
    }

    return breaks.filter(function (value, valueIndex, list) {
      return list.indexOf(value) === valueIndex;
    }).sort(function (a, b) { return a - b; });
  }

  function splitEditorContent(editor, parts) {
    var nodes = getCleanNodes(editor.getContent({ format: 'html' }));
    var breaks = [];
    var output = [];
    var preferred = preferredBreakIndexes(nodes);

    if (nodes.length <= parts) {
      insertPageBreak(editor);
      return;
    }

    breaks = computeBreaks(nodes, parts, preferred);

    nodes.forEach(function (node, nodeIndex) {
      if (breaks.indexOf(nodeIndex) !== -1) {
        output.push(PAGE_BREAK);
      }
      output.push(nodeToHtml(node));
    });

    if (editor.undoManager && typeof editor.undoManager.transact === 'function') {
      editor.undoManager.transact(function () {
        editor.setContent(output.join('\n'));
      });
    } else {
      editor.setContent(output.join('\n'));
    }

    editor.nodeChanged();

    if (typeof editor.fire === 'function') {
      editor.fire('change');
    } else if (typeof editor.dispatch === 'function') {
      editor.dispatch('change');
    }
  }

  function addToolbarButton(editor, name, settings) {
    if (typeof editor.addButton === 'function') {
      editor.addButton(name, {
        text: settings.text,
        tooltip: settings.tooltip,
        onclick: settings.action
      });
      return;
    }

    if (editor.ui && editor.ui.registry && typeof editor.ui.registry.addButton === 'function') {
      editor.ui.registry.addButton(name, {
        text: settings.text,
        tooltip: settings.tooltip,
        onAction: settings.action
      });
    }
  }

  if (typeof tinymce === 'undefined' || !tinymce.PluginManager) {
    return;
  }

  tinymce.PluginManager.add('kepoli_author_tools', function (editor) {
    addToolbarButton(editor, 'kepoli_page_break', {
      text: 'Break',
      tooltip: 'Add a page break at the cursor',
      action: function () {
        insertPageBreak(editor);
      }
    });

    addToolbarButton(editor, 'kepoli_split_two', {
      text: '2 parts',
      tooltip: 'Split content into two pages',
      action: function () {
        splitEditorContent(editor, 2);
      }
    });

    addToolbarButton(editor, 'kepoli_split_three', {
      text: '3 parts',
      tooltip: 'Split content into three pages',
      action: function () {
        splitEditorContent(editor, 3);
      }
    });

    return {
      getMetadata: function () {
        return {
          name: 'Author Tools',
          url: ''
        };
      }
    };
  });
})();
