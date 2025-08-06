/**
 * Load Events
 *
 * Wait for the page to load the content,
 * then apply any scripts contained in this eventlistener
 *
 **/
document.addEventListener('DOMContentLoaded', () => {
  // Load your scripts inside here
	function toggleNavigation() {
		const navButton = document.querySelector("button[aria-expanded]");

		function handleClick(event) {
			const expanded =
				event.target.getAttribute("aria-expanded") === "true" || false;
			navButton.setAttribute("aria-expanded", !expanded);
		}

		navButton.addEventListener("click", handleClick);
	}

  function submenuA11y() {
    const nav = document.getElementById("nav-primary");
    const menuItems = nav.querySelectorAll(".menu-item-has-children");
    let isMobile = window.innerWidth <= 768;

    // Update mobile state on resize
    window.addEventListener("resize", () => {
      isMobile = window.innerWidth <= 768;
      if (!isMobile) {
        // Reset mobile states when switching to desktop
        menuItems.forEach((menuItem) => {
          menuItem.classList.remove("mobile-open");
          closeSubmenu(menuItem);
        });
      }
    });

    menuItems.forEach((menuItem) => {
      const link = menuItem.querySelector("a");
      const submenu = menuItem.querySelector(".sub-menu");
      const submenuItems = submenu.querySelectorAll(".menu-item a");

      link.setAttribute("aria-haspopup", "true");
      link.setAttribute("aria-expanded", "false");
      submenu.setAttribute("role", "menu");
      submenu.setAttribute("aria-hidden", "true");

      submenuItems.forEach((item) => {
        item.setAttribute("role", "menuitem");
        item.setAttribute("tabindex", "-1");
      });

      if (!isMobile) {
        menuItem.addEventListener("mouseenter", () => openSubmenu(menuItem));
        menuItem.addEventListener("mouseleave", () => closeSubmenu(menuItem));
      }

      // Touch/click events - mobile
      link.addEventListener("click", (e) => {
        if (isMobile) {
          e.preventDefault();
          toggleSubmenuMobile(menuItem);
        }
      });

      // Keyboard events for parent link
      link.addEventListener("keydown", (e) => {
        switch (e.key) {
          case "Enter":
          case " ":
            e.preventDefault();
            if (isMobile) {
              toggleSubmenuMobile(menuItem);
            } else {
              if (submenu.getAttribute("aria-hidden") === "true") {
                openSubmenu(menuItem);
                focusFirstSubmenuItem(submenu);
              } else {
                closeSubmenu(menuItem);
              }
            }
            break;
          case "ArrowDown":
            if (!isMobile) {
              e.preventDefault();
              openSubmenu(menuItem);
              focusFirstSubmenuItem(submenu);
            }
            break;
          case "Escape":
            closeSubmenu(menuItem);
            link.focus();
            break;
        }
      });

      if (!isMobile) {
        submenuItems.forEach((item, index) => {
          item.addEventListener("keydown", (e) => {
            switch (e.key) {
              case "ArrowDown": {
                e.preventDefault();
                const nextIndex = (index + 1) % submenuItems.length;
                submenuItems[nextIndex].focus();
                break;
              }
              case "ArrowUp": {
                e.preventDefault();
                const prevIndex =
                  index === 0 ? submenuItems.length - 1 : index - 1;
                submenuItems[prevIndex].focus();
                break;
              }
              case "Escape":
                e.preventDefault();
                closeSubmenu(menuItem);
                link.focus();
                break;
              case "Tab":
                closeSubmenu(menuItem);
                break;
            }
          });

          item.addEventListener("blur", () => {
            setTimeout(() => {
              if (!menuItem.contains(document.activeElement)) {
                closeSubmenu(menuItem);
              }
            }, 100);
          });
        });
      }
    });

    function openSubmenu(menuItem) {
      const link = menuItem.querySelector("a");
      const submenu = menuItem.querySelector(".sub-menu");

      link.setAttribute("aria-expanded", "true");
      submenu.setAttribute("aria-hidden", "false");
      menuItem.classList.add("is-open");
    }

    function closeSubmenu(menuItem) {
      const link = menuItem.querySelector("a");
      const submenu = menuItem.querySelector(".sub-menu");
      const submenuItems = submenu.querySelectorAll(".menu-item a");

      link.setAttribute("aria-expanded", "false");
      submenu.setAttribute("aria-hidden", "true");
      menuItem.classList.remove("is-open", "mobile-open");

      submenuItems.forEach((item) => {
        item.setAttribute("tabindex", "-1");
      });
    }

    function toggleSubmenuMobile(menuItem) {
      const isOpen = menuItem.classList.contains("mobile-open");

      // Close all other open submenus on mobile
      menuItems.forEach((item) => {
        if (item !== menuItem) {
          closeSubmenu(item);
        }
      });

      if (isOpen) {
        closeSubmenu(menuItem);
      } else {
        openSubmenu(menuItem);
        menuItem.classList.add("mobile-open");
      }
    }

    function focusFirstSubmenuItem(submenu) {
      if (!isMobile) {
        const firstItem = submenu.querySelector(".menu-item a");
        if (firstItem) {
          firstItem.setAttribute("tabindex", "0");
          firstItem.focus();
        }
      }
    }

    document.addEventListener("click", (e) => {
      if (!nav.contains(e.target)) {
        menuItems.forEach((menuItem) => closeSubmenu(menuItem));
      }
    });
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
