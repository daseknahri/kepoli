(() => {
  const toggle = document.querySelector('[data-nav-toggle]');
  const panel = document.querySelector('[data-nav-panel]');

  if (toggle && panel) {
    const closePanel = () => {
      toggle.classList.remove('is-open');
      toggle.setAttribute('aria-expanded', 'false');
      panel.classList.remove('is-open');
    };

    const openPanel = () => {
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
      if (!panel.contains(event.target) && !toggle.contains(event.target)) {
        closePanel();
      }
    });

    document.addEventListener('keydown', (event) => {
      if (event.key === 'Escape') {
        closePanel();
      }
    });

    window.addEventListener('resize', () => {
      if (window.innerWidth > 980) {
        closePanel();
      }
    });
  }

  const progress = document.querySelector('[data-reading-progress]');
  const progressBar = document.querySelector('[data-reading-progress-bar]');
  const source = document.querySelector('[data-reading-progress-source]');

  if (progress && progressBar && source) {
    const updateProgress = () => {
      const rect = source.getBoundingClientRect();
      const total = source.offsetHeight - window.innerHeight;

      if (total <= 0) {
        progress.hidden = true;
        return;
      }

      const current = Math.min(Math.max(-rect.top, 0), total);
      const value = Math.max(0, Math.min(100, (current / total) * 100));

      progress.hidden = false;
      progressBar.style.width = `${value}%`;
    };

    updateProgress();
    document.addEventListener('scroll', updateProgress, { passive: true });
    window.addEventListener('resize', updateProgress);
  }

  document.querySelectorAll('[data-copy-url]').forEach((button) => {
    button.addEventListener('click', async () => {
      const url = button.getAttribute('data-copy-url') || '';
      const defaultLabel = button.getAttribute('data-copy-default') || 'Copy';
      const successLabel = button.getAttribute('data-copy-success') || 'Copied';
      const label = button.querySelector('span:last-child');

      try {
        if (navigator.clipboard && window.isSecureContext) {
          await navigator.clipboard.writeText(url);
        } else {
          const input = document.createElement('input');
          input.value = url;
          document.body.appendChild(input);
          input.select();
          document.execCommand('copy');
          input.remove();
        }

        if (label) {
          label.textContent = successLabel;
          window.setTimeout(() => {
            label.textContent = defaultLabel;
          }, 1800);
        }
      } catch (error) {
        console.error(error);
      }
    });
  });

  document.querySelectorAll('[data-print-page]').forEach((button) => {
    button.addEventListener('click', () => {
      window.print();
    });
  });
})();
