/**
 * Load Events
 *
 * Wait for the page to load the content,
 * then apply any scripts contained in this eventlistener
 *
 **/
document.addEventListener("DOMContentLoaded", () => {
  // Load your scripts inside here
	function toggleNavigation() {
		const navButton = document.querySelector("button[aria-expanded]");

		function handleClick(event) {
			const expanded =
				event.currentTarget.getAttribute("aria-expanded") === "true" || false;
			navButton.setAttribute("aria-expanded", !expanded);
      navButton.setAttribute("aria-pressed", !expanded);
		}

		navButton.addEventListener("click", handleClick);
	}

  function submenuA11y() {
    const nav = document.getElementById("nav-primary");
    const menuItems = nav.querySelectorAll(".menu-item-has-children");
    const allTopLevelItems = nav.querySelectorAll(".nav-list > .menu-item > a");

    const BREAKPOINT = 768;
    const KEYS = {
      ENTER: 'Enter',
      ARROW_DOWN: 'ArrowDown',
      ARROW_UP: 'ArrowUp',
      ARROW_LEFT: 'ArrowLeft',
      ARROW_RIGHT: 'ArrowRight',
      ESCAPE: 'Escape',
      TAB: 'Tab'
    };

    let isMobile = window.innerWidth <= BREAKPOINT;
    let currentOpenSubmenu = null;
    const elementCache = new WeakMap();
    const abortController = new AbortController();
    const { signal } = abortController;

    const debounce = (func, wait) => {
      let timeout;
      return (...args) => {
        clearTimeout(timeout);
        timeout = setTimeout(() => func(...args), wait);
      };
    };

    const getMenuItemElements = (menuItem) => {
      if (!elementCache.has(menuItem)) {
        const link = menuItem.querySelector("a");
        const submenu = menuItem.querySelector(".sub-menu");
        elementCache.set(menuItem, {
          link,
          submenu,
          submenuItems: [...submenu.querySelectorAll(".menu-item a")]
        });
      }
      return elementCache.get(menuItem);
    };

    const handleResize = debounce(() => {
      const wasMobile = isMobile;
      isMobile = window.innerWidth <= BREAKPOINT;
      if (wasMobile && !isMobile) { closeAllSubmenus() };
    }, 100);

    const initializeMenuItems = () => {
      menuItems.forEach((menuItem) => {
        const { link } = getMenuItemElements(menuItem);
        Object.assign(link, {
          'aria-haspopup': 'true',
          'aria-expanded': 'false'
        });
      });
    };

    const handleTopLevelKeydown = (e, menuItem) => {
      const { link } = getMenuItemElements(menuItem);
      const isSubmenuOpen = link.getAttribute("aria-expanded") === "true";

      if (e.key === KEYS.ENTER) {
        e.preventDefault();
        setSubmenuState(menuItem, isMobile ? !isSubmenuOpen : !isSubmenuOpen, !isMobile && !isSubmenuOpen);
      } else if (e.key === KEYS.ESCAPE) {
        setSubmenuState(menuItem, false);
        link.focus();
      }
    };

    const handleSubmenuKeydown = (e, menuItem, currentItem) => {
      const { submenuItems, link } = getMenuItemElements(menuItem);
      const currentIndex = submenuItems.indexOf(currentItem);
      const itemCount = submenuItems.length;

      switch (e.key) {
        case KEYS.ARROW_DOWN:
          e.preventDefault();
          submenuItems[(currentIndex + 1) % itemCount].focus();
          break;
        case KEYS.ARROW_UP:
          e.preventDefault();
          submenuItems[currentIndex === 0 ? itemCount - 1 : currentIndex - 1].focus();
          break;
        case KEYS.ARROW_LEFT:
        case KEYS.ARROW_RIGHT:
          e.preventDefault();
          setSubmenuState(menuItem, false);
          navigateToSiblingMenu(link, e.key === KEYS.ARROW_RIGHT);
          break;
        case KEYS.ESCAPE:
          e.preventDefault();
          setSubmenuState(menuItem, false);
          link.focus();
          break;
        case KEYS.TAB:
          setSubmenuState(menuItem, false);
          break;
      }
    };

    const setSubmenuState = (menuItem, isOpen, focusFirst = false) => {
      if (!menuItem) { return };

      const { link, submenuItems } = getMenuItemElements(menuItem);

      if (isOpen && currentOpenSubmenu && currentOpenSubmenu !== menuItem) {
        setSubmenuState(currentOpenSubmenu, false);
      }

      link.setAttribute("aria-expanded", String(isOpen));

      currentOpenSubmenu = isOpen ? menuItem : (currentOpenSubmenu === menuItem ? null : currentOpenSubmenu);

      if (isOpen && focusFirst && !isMobile && submenuItems?.[0]) {
        submenuItems[0].focus();
      }
    };

    const closeAllSubmenus = () => {
      menuItems.forEach(menuItem => {
        const { link } = getMenuItemElements(menuItem);
        if (link.getAttribute("aria-expanded") === "true") {
          setSubmenuState(menuItem, false);
        }
      });
    };

    const toggleSubmenu = (menuItem) => {
      const { link } = getMenuItemElements(menuItem);
      const isOpen = link.getAttribute("aria-expanded") === "true";

      menuItems.forEach(item => item !== menuItem && setSubmenuState(item, false));

      setSubmenuState(menuItem, !isOpen);
    };

    const navigateToSiblingMenu = (currentLink, goRight) => {
      const currentIndex = [...allTopLevelItems].indexOf(currentLink);
      const targetIndex = currentIndex + (goRight ? 1 : -1);

      const targetLink = allTopLevelItems[targetIndex];
      targetLink?.focus();
    };

    nav.addEventListener("click", (e) => {
      const menuItem = e.target.closest(".menu-item-has-children");
      if (!menuItem) { return };

      const { link } = getMenuItemElements(menuItem);
      if (e.target === link && link.href.endsWith("#") && isMobile) {
        e.preventDefault();
        toggleSubmenu(menuItem);
      }
    }, { signal });

    nav.addEventListener("keydown", (e) => {
      const menuItem = e.target.closest(".menu-item-has-children");
      if (!menuItem) { return };

      const { link } = getMenuItemElements(menuItem);
      const isTopLevelLink = e.target === link;
      const isSubmenuItem = e.target.tagName === "A" && e.target.closest(".sub-menu");

      if (isTopLevelLink) {
        handleTopLevelKeydown(e, menuItem);
      } else if (isSubmenuItem && !isMobile) {
        handleSubmenuKeydown(e, menuItem, e.target);
      }
    }, { signal });

    nav.addEventListener("mouseenter", (e) => {
      if (isMobile) { return };
      const menuItem = e.target.closest(".menu-item-has-children");
      if (menuItem && e.target === menuItem) { setSubmenuState(menuItem, true) };
    }, { capture: true, passive: true, signal });

    nav.addEventListener("mouseleave", (e) => {
      if (isMobile) { return };
      const menuItem = e.target.closest(".menu-item-has-children");
      if (menuItem && e.target === menuItem) { setSubmenuState(menuItem, false) };
    }, { capture: true, passive: true, signal });

    window.addEventListener("resize", handleResize, { signal });

    document.addEventListener("click", (e) => {
      if (isMobile) { return };
      if (!nav.contains(e.target)) { closeAllSubmenus() };
    }, { signal });

    initializeMenuItems();

    return () => {
      abortController.abort();
      currentOpenSubmenu = null;

      if (handleResize.timeout) {
        clearTimeout(handleResize.timeout);
      }
    };
  }

	function closeDialog() {
		const searchDialog = document.getElementById("search-dialog");

		function handleKeyDown(event) {
			searchDialog.contains(document.activeElement) &&
				event.key === "Escape" &&
				searchDialog.open &&
				searchDialog.close();
		}

		document.addEventListener("keydown", handleKeyDown);

		return () => {
			document.removeEventListener("keydown", handleKeyDown);
		}
	}

	toggleNavigation();
	submenuA11y();
	closeDialog();
});
