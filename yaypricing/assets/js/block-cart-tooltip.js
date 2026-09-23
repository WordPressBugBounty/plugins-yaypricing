/**
 * Cart item price tooltip inside the WooCommerce Cart, Checkout and Mini-Cart blocks.
 *
 * The blocks render line items in React from the Store API, so the classic
 * `woocommerce_cart_item_price` filter never runs there. The tooltip HTML arrives as
 * `cart.items[].extensions.yaypricing.tooltips` (see
 * includes/core/blocks/class-yaydp-store-api-cart-item-extension.php); each row is
 * tagged with its cart item key through the official `cartItemClass` checkout filter,
 * and the same markup the classic template prints is mounted after the row's price.
 *
 * The official `cartItemPrice` filter cannot be used: it only accepts a text string
 * with a `<price/>` placeholder. DOM class names below were verified against
 * WooCommerce 11.0 (cart, checkout and mini-cart share the row markup).
 */
(function () {
  const wc = window.wc;
  const wp = window.wp;
  if (
    !wc?.blocksCheckout?.registerCheckoutFilters ||
    !wp?.data?.select ||
    !window.yaydpTooltip?.build
  ) {
    return;
  }

  const ROW_CLASS_PREFIX = "yaydp-cart-item--";
  const PRICE_SELECTOR =
    ".wc-block-cart-item__prices .wc-block-components-product-price";
  const ICON_SELECTOR = ".yaydp-tooltip-icon";

  wc.blocksCheckout.registerCheckoutFilters("yaypricing", {
    cartItemClass: (value, extensions, args) => {
      const key = args?.cartItem?.key;
      return key ? `${value} ${ROW_CLASS_PREFIX}${key}`.trim() : value;
    },
  });

  function cartItems() {
    const store = wp.data.select("wc/store/cart");
    const data = store?.getCartData?.();
    return data?.items || [];
  }

  function rowsFor(key) {
    const cls = ROW_CLASS_PREFIX + key;
    const selector = window.CSS?.escape ? `.${CSS.escape(cls)}` : `.${cls}`;
    return document.querySelectorAll(selector);
  }

  // Idempotent sync of each tagged row with the store: rows stay mounted across
  // cart updates (WC keys them by item key and overlays a loading mask), so an
  // existing icon is replaced when its contents change and removed when the
  // item's rules no longer apply; React remounts (drawer reopen) simply get a
  // fresh icon.
  function mount() {
    cartItems().forEach((item) => {
      if (!item?.key) {
        return;
      }
      const tooltips = item.extensions?.yaypricing?.tooltips;
      const contents = Array.isArray(tooltips) ? tooltips : [];
      const signature = JSON.stringify(contents);
      rowsFor(item.key).forEach((row) => {
        const price = row.querySelector(PRICE_SELECTOR);
        const existing = row.querySelector(ICON_SELECTOR);
        if (existing && existing.dataset.yaydpSignature === signature) {
          return;
        }
        if (existing) {
          existing.remove();
        }
        if (!price || contents.length === 0) {
          return;
        }
        const icon = window.yaydpTooltip.build(contents);
        icon.dataset.yaydpSignature = signature;
        price.insertAdjacentElement("afterend", icon);
      });
    });
  }

  // Debounced with a macrotask rather than requestAnimationFrame: store updates and
  // DOM mutations arrive in bursts, and rAF is paused in background tabs.
  let timer = 0;
  function schedule() {
    window.clearTimeout(timer);
    timer = window.setTimeout(mount, 0);
  }

  wp.data.subscribe(schedule);
  new MutationObserver(schedule).observe(document.body, {
    childList: true,
    subtree: true,
  });
  schedule();
})();
