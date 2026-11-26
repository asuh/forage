import { submenuA11y, toggleNavigation } from './navigation.js';

/**
 * Load Events
 *
 * Wait for the page to load the content,
 * then apply any scripts contained in this eventlistener
 *
 **/
document.addEventListener('DOMContentLoaded', () => {
  // Load your scripts inside here
  const noop = () => undefined;

  function initializeDialogs() {
    const dialogs = document.querySelectorAll('dialog[id]');

    if (!dialogs.length) {
      return noop;
    }

    if (!('HTMLDialogElement' in window)) {
      return noop;
    }

    const supportsInvokerCommands =
      'commandForElement' in HTMLButtonElement.prototype;
    const supportsLightDismiss = 'closedBy' in HTMLDialogElement.prototype;
    const abortController = new AbortController();
    const { signal } = abortController;

    document.querySelectorAll('button[commandfor]').forEach((button) => {
      const dialog = document.getElementById(button.getAttribute('commandfor'));

      if (!(dialog instanceof HTMLDialogElement) || supportsInvokerCommands) {
        return;
      }

      const command = button.getAttribute('command');

      if (command === 'show-modal') {
        button.addEventListener(
          'click',
          () => {
            if (!dialog.open) {
              dialog.showModal();
            }
          },
          { signal },
        );
      } else if (command === 'close') {
        button.addEventListener(
          'click',
          () => {
            if (dialog.open) {
              dialog.close();
            }
          },
          { signal },
        );
      }
    });

    if (!supportsLightDismiss) {
      dialogs.forEach((dialog) => {
        dialog.addEventListener(
          'click',
          (event) => {
            if (event.target === dialog) {
              dialog.close('dismiss');
            }
          },
          { signal },
        );
      });
    }

    return () => abortController.abort();
  }

  function appendLoadTime() {
    const appendTiming = () => {
      const navigation = window.performance
        ?.getEntriesByType('navigation')
        .find((entry) => entry.entryType === 'navigation');
      const copyright = document.querySelector('.copyright');

      if (!navigation || !copyright) {
        return;
      }

      const loadTime = navigation.duration / 1000;

      if (!Number.isFinite(loadTime) || loadTime <= 0) {
        return;
      }

      const element = document.createElement('small');
      element.textContent = `This page loaded in ${loadTime.toFixed(2)} seconds`;
      copyright.appendChild(element);
    };

    if (document.readyState === 'complete') {
      appendTiming();
      return;
    }

    window.addEventListener(
      'load',
      () => {
        window.setTimeout(appendTiming, 0);
      },
      { once: true },
    );
  }

  toggleNavigation();
  submenuA11y();
  initializeDialogs();
  if (import.meta.env.DEV) {
    appendLoadTime();
  }
});
