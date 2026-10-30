(function ($) {
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
