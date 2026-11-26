const noop = () => undefined;

export function toggleNavigation() {
  const navButton = document.querySelector(
    'button[aria-expanded][aria-controls="nav-primary"]',
  );

  if (!navButton) {
    return noop;
  }

  navButton.hidden = false;

  function handleClick(event) {
    const expanded =
      event.currentTarget.getAttribute('aria-expanded') === 'true';
    navButton.setAttribute('aria-expanded', String(!expanded));
  }

  navButton.addEventListener('click', handleClick);

  return () => {
    navButton.removeEventListener('click', handleClick);
  };
}

export function submenuA11y() {
  const nav = document.getElementById('nav-primary');

  if (!nav) {
    return noop;
  }

  const menuItemSelector = '.menu-item:has(> .sub-menu)';
  const menuItems = [...nav.querySelectorAll(menuItemSelector)];

  if (!menuItems.length) {
    return noop;
  }

  const mobileQuery = window.matchMedia('(width < 48em)');
  const abortController = new AbortController();
  const { signal } = abortController;
  let isMobile = mobileQuery.matches;
  let pointerType = '';

  const getButton = (menuItem) =>
    menuItem?.querySelector(':scope > button[aria-expanded]');

  const isOpen = (menuItem) =>
    getButton(menuItem)?.getAttribute('aria-expanded') === 'true';

  const setOpen = (menuItem, open) => {
    if (isMobile || !getButton(menuItem)) {
      return;
    }

    for (const item of menuItems) {
      const affected = open
        ? !item.contains(menuItem)
        : menuItem.contains(item);
      if (item === menuItem || affected) {
        getButton(item)?.setAttribute(
          'aria-expanded',
          String(item === menuItem && open),
        );
      }
    }
  };

  const closeAll = () => {
    for (const item of menuItems.filter(isOpen)) {
      setOpen(item, false);
    }
  };

  const initialize = () => {
    for (const menuItem of menuItems) {
      const button = getButton(menuItem);
      if (!button) {
        continue;
      }

      button.hidden = isMobile && Boolean(menuItem.querySelector(':scope > a'));
      button.setAttribute('aria-expanded', String(isMobile));
    }

    // Resizing can leave the pointer over an item without firing mouseenter.
    if (!isMobile) {
      setOpen(
        menuItems.find((menuItem) => menuItem.matches(':hover')),
        true,
      );
    }
  };

  nav.addEventListener(
    'pointerdown',
    (e) => {
      pointerType = e.pointerType;
    },
    { capture: true, passive: true, signal },
  );

  nav.addEventListener(
    'click',
    (e) => {
      if (isMobile) {
        return;
      }

      const menuItem = e.target.closest(menuItemSelector);
      const button = getButton(menuItem);
      if (!button?.contains(e.target)) {
        return;
      }

      const open = isOpen(menuItem);
      if (
        open &&
        e.detail > 0 &&
        pointerType === 'mouse' &&
        menuItem.matches(':hover')
      ) {
        return;
      }

      setOpen(menuItem, !open);
    },
    { signal },
  );

  nav.addEventListener(
    'keydown',
    (e) => {
      if (isMobile || e.key !== 'Escape') {
        return;
      }

      let menuItem = e.target.closest(menuItemSelector);
      while (menuItem && !isOpen(menuItem)) {
        menuItem = menuItem.parentElement.closest(menuItemSelector);
      }

      if (menuItem) {
        e.preventDefault();
        setOpen(menuItem, false);
        getButton(menuItem).focus();
      }
    },
    { signal },
  );

  nav.addEventListener(
    'focusout',
    (e) => {
      for (const item of menuItems.filter(isOpen)) {
        if (
          item.contains(e.target) &&
          !item.contains(e.relatedTarget) &&
          !item.matches(':hover')
        ) {
          setOpen(item, false);
        }
      }
    },
    { signal },
  );

  nav.addEventListener(
    'pointerenter',
    (e) => {
      const menuItem = e.target;
      if (
        e.pointerType !== 'mouse' ||
        !menuItem.matches?.(menuItemSelector) ||
        menuItems.some(
          (item) =>
            isOpen(item) &&
            !item.contains(menuItem) &&
            item.querySelector(':scope > .sub-menu :focus-visible'),
        )
      ) {
        return;
      }

      setOpen(menuItem, true);
    },
    { capture: true, passive: true, signal },
  );

  nav.addEventListener(
    'pointerleave',
    (e) => {
      if (
        e.pointerType === 'mouse' &&
        e.target.matches?.(menuItemSelector) &&
        !e.target.querySelector(':focus-visible')
      ) {
        setOpen(e.target, false);
      }
    },
    { capture: true, passive: true, signal },
  );

  mobileQuery.addEventListener(
    'change',
    (event) => {
      isMobile = event.matches;
      initialize();
    },
    { signal },
  );

  document.addEventListener(
    'click',
    (e) => {
      if (!nav.contains(e.target)) {
        closeAll();
      }
    },
    { signal },
  );

  initialize();

  return () => {
    abortController.abort();
  };
}
