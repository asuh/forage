(() => {
  const stylesheet = new URL('../styles/editor.css?direct=1', import.meta.url);
  const marker = '--forage-editor-live-css';
  const store = 'core/editor';
  const maxFailures = 6;
  let failures = 0;

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
        failures += 1;
        interval = 5000;
        return;
      }

      failures = 0;
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
      failures += 1;
      interval = 5000;
    } finally {
      if (failures < maxFailures) {
        window.setTimeout(refresh, interval);
      } else {
        console.info('Forage live editor CSS stopped because Vite is unavailable. Reload the editor after restarting Vite.');
      }
    }
  }

  refresh();
})();
