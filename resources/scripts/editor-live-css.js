(() => {
  const stylesheet = new URL('../styles/editor.css?direct=1', import.meta.url);
  const marker = '--forage-editor-live-css';
  const store = 'core/editor';

  async function refresh() {
    let interval = 1000;

    try {
      if (document.hidden || !window.wp?.data) {
        return;
      }

      const query = window.wp.data.select(store);
      const update = window.wp.data.dispatch(store)?.updateEditorSettings;
      if (typeof update !== 'function' || !query?.getEditorSettings?.()?.styles?.some(style => style.css?.includes(marker))) {
        return;
      }

      const response = await fetch(stylesheet, { cache: 'no-cache' });
      if (!response.ok || !response.headers.get('content-type')?.startsWith('text/css')) {
        interval = 5000;
        return;
      }

      const css = await response.text();
      if (!css.includes(marker)) {
        return;
      }

      const styles = query.getEditorSettings()?.styles;
      const current = styles?.find(style => style.css?.includes(marker));
      if (current && current.css !== css) {
        update({ styles: styles.map(style => style === current ? { ...style, css } : style) });
      }
    } catch {
      // Retry after Vite restarts without clearing the stylesheet or reloading WordPress.
      interval = 5000;
    } finally {
      window.setTimeout(refresh, interval);
    }
  }

  refresh();
})();
