"use strict";
(function ($) {
  jQuery(document).ready(function ($) {
    const localizeData = window.yaydp_frontend_data ?? {};
    const discount_based_on = localizeData.discount_based_on ?? "regular_price";
    $(".single_variation_wrap").on(
      "show_variation",
      function (event, variation) {
        $("#yaydp-offer-description").show();
        $(".yaydp-pricing-table-wrapper").show();
        $(
          "[data-variable='discount_value'], [data-variable='final_price'], [data-variable='discount_amount'], [data-variable='discounted_price']"
        ).each((index, item) => {
          const currency = $(item)
            .find(".woocommerce-Price-currencySymbol")
            .first()
            .html();
          const variation_price =
            discount_based_on === "regular_price"
              ? variation.display_regular_price
              : variation.display_price;
          const formula = $(item).data("formula");
          const final_price = eval(formula.replaceAll("x", variation_price));
          $(item)
            .find(".woocommerce-Price-amount")
            .html(
              `<span class="woocommerce-Price-currencySymbol">${currency}</span>${isNaN(final_price) ? final_price : final_price.toFixed(2)}`
            );
        });
      }
    );
    $(".single_variation_wrap").on("hide_variation", function (event) {
      $("#yaydp-offer-description").hide();
      $(".yaydp-pricing-table-wrapper").hide();
    });
  });
})(jQuery);
