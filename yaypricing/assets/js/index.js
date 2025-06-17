(function ($) {
  $(document).on("mouseenter", ".yaydp-tooltip-icon", function () {
    const tooltip = $(this).find(".yaydp-tooltip-content");
    if (tooltip.length < 1) {
      return;
    }
    const { left } = tooltip.offset();
    const width = tooltip.width();
    console.log(width, left);
    if (width + left > window.outerWidth) {
      tooltip.css("left", window.outerWidth - width - left - 30);
    }
    if (left < 0) {
      tooltip.css("left", 30);
    }
  });

  $(document).ready(function () {
    if (!window.elementorFrontend || !window.elementorFrontend.getElements) {
      return;
    }
    const menuCarts = window.elementorFrontend
      ?.getElements()
      ?.$body?.find(".elementor-widget-woocommerce-menu-cart");
    menuCarts.each((_, el) => {
      const settings = $(el).data("settings");
      if (!settings) {
        return;
      }
      if (settings.automatically_open_cart == null) {
        return;
      }
      const currentValue = settings.automatically_open_cart;
      settings.automatically_open_cart = "no";
      $(el).data("settings", settings);
      setTimeout(() => {
        settings.automatically_open_cart = currentValue;
        $(el).data("settings", settings);
      }, 1000);
    });
  });

  /**
   * Integrate with YayExtra product total
   */
  if (!window.wp?.hooks?.addFilter) {
    return;
  }
  window.wp.hooks.addFilter(
    "yaye_total_price_hook",
    "yaydp",
    function (html, total) {
      const extraTotalPrice = $(".yayextra-total-price");
      if (extraTotalPrice.closest(".product").length < 1) {
        return html;
      }
      const yaydpDiscountedData = extraTotalPrice
        .closest(".single-product-wrapper")
        .find(".yaydp-product-discounted-data");
      if (yaydpDiscountedData.length < 1) {
        return html;
      }

      const minRate = yaydpDiscountedData.data("min-rate");
      const maxRate = yaydpDiscountedData.data("min-rate");

      if (minRate >= 1) {
        return html;
      }

      const discounted = total * minRate;

      function formatPrice(price) {
        window.accounting.settings.currency.symbol =
          window.yaydp_frontend_data.currency_settings.symbol;
        window.accounting.settings.currency.decimal =
          window.yaydp_frontend_data.currency_settings.decimalSeparator;
        window.accounting.settings.currency.thousand =
          window.yaydp_frontend_data.currency_settings.thousandSeparator;
        window.accounting.settings.currency.precision =
          window.yaydp_frontend_data.currency_settings.precision;
        return `
        <span class="woocommerce-Price-amount amount">${window.accounting.formatMoney(
          price
        )}</span>
        `;
      }

      return `
      <del>${formatPrice(total)}</del>
      ${formatPrice(discounted)}`;
    }
  );
})(window.jQuery);
