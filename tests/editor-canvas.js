(() => {
  const canvas = document.querySelector('iframe[name="editor-canvas"]')?.contentDocument;
  const body = canvas?.body;
  if (!body?.classList.contains('editor-styles-wrapper')) {
    throw new Error('Open a WordPress page in the block editor and wait for the canvas to load.');
  }

  const editor = window.wp?.data?.select('core/block-editor');
  const compiled = editor?.getSettings().styles?.find(({ baseURL, css }) =>
    baseURL && /\/dist\/[^/]+\.css$/.test(new URL(baseURL, location.href).pathname) &&
    css?.includes('--forage-editor-styles-loaded')
  );
  if (!compiled) {
    throw new Error('WordPress did not register the compiled Forage editor stylesheet. Build the theme and stop Vite.');
  }

  const view = canvas.defaultView;
  const marker = view.getComputedStyle(body).getPropertyValue('--forage-editor-styles-loaded').trim();
  if (marker !== '1') {
    throw new Error('The compiled Forage stylesheet was registered but did not apply inside the editor iframe.');
  }

  const probe = canvas.createElement('div');
  probe.style.position = 'absolute';
  probe.style.visibility = 'hidden';
  probe.style.paddingLeft = 'var(--wp--preset--spacing--50)';
  probe.style.paddingRight = 'var(--space-50)';
  body.append(probe);
  try {
    const { paddingLeft, paddingRight } = view.getComputedStyle(probe);
    const preset = parseFloat(paddingLeft);
    const token = parseFloat(paddingRight);
    if (!(preset > 0 && Math.abs(preset - token) < 0.01)) {
      throw new Error('The Medium WordPress spacing preset did not resolve to the Forage CSS token.');
    }

    return { canvasWidth: view.innerWidth, mediumSpacingPx: preset };
  } finally {
    probe.remove();
  }
})();
