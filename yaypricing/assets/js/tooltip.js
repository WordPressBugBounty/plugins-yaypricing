(function () {
  /**
   * Rule tooltip (".yaydp-tooltip-icon"): cart item prices, coupon rows, fee rows.
   * Hover is pure CSS; this adds keyboard/touch open-close, keeps the bubble inside
   * the viewport (or its scroll container), and exposes a builder so
   * block-cart-tooltip.js can render the same markup as includes/templates/tooltip.php.
   * No jQuery.
   */
  const ICON = ".yaydp-tooltip-icon";
  const CONTENT = ".yaydp-tooltip-content";
  const OPEN = "is-open";
  const BELOW = "is-below";
  const VIEWPORT_MARGIN = 8;
  let tooltipCounter = 0;

  function build(contents) {
    const id = `yaydp-tooltip-js-${++tooltipCounter}`;
    const icon = document.createElement("span");
    icon.className = "yaydp-tooltip-icon";
    icon.tabIndex = 0;
    icon.setAttribute("role", "button");
    icon.setAttribute("aria-expanded", "false");
    icon.setAttribute("aria-describedby", id);
    icon.setAttribute(
      "aria-label",
      window.yaydp_tooltip_data?.label || "Discount details"
    );
    const content = document.createElement("div");
    content.className = "yaydp-tooltip-content";
    content.id = id;
    content.setAttribute("role", "tooltip");
    contents.forEach((html) => {
      const row = document.createElement("div");
      row.innerHTML = html; // server-sanitised (wp_kses_post) rule content
      content.appendChild(row);
    });
    icon.appendChild(content);
    return icon;
  }

  // Top edge the bubble must not cross: the nearest scrolling ancestor (e.g. the
  // mini-cart drawer's item list clips overflow) or, failing that, the viewport.
  function clipTop(icon) {
    let node = icon.parentElement;
    while (node && node !== document.body) {
      const overflowY = window.getComputedStyle(node).overflowY;
      if (overflowY === "auto" || overflowY === "scroll") {
        return Math.max(0, node.getBoundingClientRect().top);
      }
      node = node.parentElement;
    }
    return 0;
  }

  // Shift the bubble horizontally so it stays inside the viewport, and flip it
  // below the icon when there is no room above. Geometry is derived from the icon
  // and the bubble's intrinsic size, so it does not depend on the CSS transition state.
  function position(icon) {
    const content = icon.querySelector(CONTENT);
    if (!content) {
      return;
    }
    const iconRect = icon.getBoundingClientRect();
    const width = content.offsetWidth;
    const height = content.offsetHeight;
    const viewportWidth = document.documentElement.clientWidth;
    const center = iconRect.left + iconRect.width / 2;
    let shift = 0;
    if (center - width / 2 < VIEWPORT_MARGIN) {
      shift = VIEWPORT_MARGIN - (center - width / 2);
    } else if (center + width / 2 > viewportWidth - VIEWPORT_MARGIN) {
      shift = viewportWidth - VIEWPORT_MARGIN - (center + width / 2);
    }
    content.style.setProperty("--yaydp-tooltip-shift", `${Math.round(shift)}px`);
    icon.classList.toggle(BELOW, iconRect.top - clipTop(icon) < height + 16);
  }

  function setOpen(icon, open) {
    icon.classList.toggle(OPEN, open);
    icon.setAttribute("aria-expanded", open ? "true" : "false");
    if (open) {
      position(icon);
    }
  }

  function closeAll(except) {
    document.querySelectorAll(`${ICON}.${OPEN}`).forEach((icon) => {
      if (icon !== except) {
        setOpen(icon, false);
      }
    });
  }

  document.addEventListener("mouseover", (event) => {
    const icon = event.target.closest?.(ICON);
    if (icon && !icon.contains(event.relatedTarget)) {
      position(icon);
    }
  });
  // Keyboard focus opens, blur closes; Escape blurs so the bubble really hides.
  document.addEventListener("focusin", (event) => {
    const icon = event.target.closest?.(ICON);
    if (icon) {
      closeAll(icon);
      setOpen(icon, true);
    }
  });
  document.addEventListener("focusout", (event) => {
    const icon = event.target.closest?.(ICON);
    if (icon && !icon.contains(event.relatedTarget)) {
      setOpen(icon, false);
    }
  });
  // Pointer taps must not focus the icon, otherwise focusin opens and the click
  // toggles it straight back shut. Keyboard (Tab) focus is unaffected.
  document.addEventListener("pointerdown", (event) => {
    if (event.target.closest?.(ICON) && !event.target.closest?.(CONTENT)) {
      event.preventDefault();
    }
  });
  document.addEventListener("click", (event) => {
    if (event.target.closest?.(CONTENT)) {
      return; // selecting text / following a link inside the bubble keeps it open
    }
    const icon = event.target.closest?.(ICON);
    closeAll(icon);
    if (icon) {
      setOpen(icon, !icon.classList.contains(OPEN));
    }
  });
  document.addEventListener("keydown", (event) => {
    if (event.key === "Escape") {
      closeAll();
      if (document.activeElement?.closest?.(ICON)) {
        document.activeElement.blur();
      }
      return;
    }
    const icon = event.target.closest?.(ICON);
    if (icon && (event.key === "Enter" || event.key === " ")) {
      event.preventDefault();
      closeAll(icon);
      setOpen(icon, !icon.classList.contains(OPEN));
    }
  });

  window.yaydpTooltip = { build, position };
})();
