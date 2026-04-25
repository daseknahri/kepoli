(() => {
  const toggle = document.querySelector('[data-nav-toggle]');
  const panel = document.querySelector('[data-nav-panel]');
  const panelInner = panel ? panel.querySelector('[data-nav-panel-inner]') || panel : null;

  if (toggle && panel) {
    const closePanel = () => {
      document.body.classList.remove('nav-open');
      toggle.classList.remove('is-open');
      toggle.setAttribute('aria-expanded', 'false');
      panel.classList.remove('is-open');
    };

    const openPanel = () => {
      document.body.classList.add('nav-open');
      toggle.classList.add('is-open');
      toggle.setAttribute('aria-expanded', 'true');
      panel.classList.add('is-open');
    };

    toggle.addEventListener('click', () => {
      if (panel.classList.contains('is-open')) {
        closePanel();
      } else {
        openPanel();
      }
    });

    document.addEventListener('click', (event) => {
      if (panelInner && !panelInner.contains(event.target) && !toggle.contains(event.target)) {
        closePanel();
      }
    });

    document.addEventListener('keydown', (event) => {
      if (event.key === 'Escape') {
        closePanel();
      }
    });

    panel.querySelectorAll('a').forEach((link) => {
      link.addEventListener('click', () => {
        closePanel();
      });
    });

    window.addEventListener('resize', () => {
      if (window.innerWidth > 980) {
        closePanel();
      }
    });
  }
})();
