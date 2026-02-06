"use strict";
(function ($) {
  jQuery(document).ready(function ($) {
    const localizeData = window.yaydp_frontend_data ?? {};
    const discount_based_on = localizeData.discount_based_on ?? "regular_price";
    const currencySettings = localizeData.currency_settings ?? {};

    function formatPrice(price) {
      if (isNaN(price)) {
        return price;
      }

      const {
        symbol = "$",
        symbolPosition = "left",
        decimalSeparator = ".",
        thousandSeparator = ",",
        precision = 2,
      } = currencySettings;

      const priceString = parseFloat(price).toFixed(precision);

      const parts = priceString.split(".");

      parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, thousandSeparator);

      const formattedNumber = parts.join(decimalSeparator);

      let formattedPrice;
      switch (symbolPosition) {
        case "left":
          formattedPrice = `${symbol}${formattedNumber}`;
          break;
        case "right":
          formattedPrice = `${formattedNumber}${symbol}`;
          break;
        case "left_space":
          formattedPrice = `${symbol} ${formattedNumber}`;
          break;
        case "right_space":
          formattedPrice = `${formattedNumber} ${symbol}`;
          break;
        default:
          formattedPrice = `${symbol}${formattedNumber}`;
      }

      return formattedPrice;
    }

    function pricingTableInitialVisibility() {
      $(".yaydp-pricing-table-wrapper").each(function () {
        const applicable_variations =
          $(this).data("applicable-variations")?.toString()?.split(",") ?? [];

        if (applicable_variations.length > 1) {
          $(this).show();
        } else {
          $(this).hide();
        }
      });
    }

    function detectQuickViewOpen() {
      initializeVariationHandlers();
    }

    function initializeVariationHandlers() {
      pricingTableInitialVisibility();
      $(".single_variation_wrap").on(
        "show_variation",
        function (event, variation) {
          pricingTableInitialVisibility();
          const applicable_variations =
            $(".yaydp-pricing-table-wrapper")
              .data("applicable-variations")
              ?.toString()
              ?.split(",") ?? [];

          if (applicable_variations.length > 1) {
            if (
              !applicable_variations.includes(variation.variation_id.toString())
            ) {
              $(".yaydp-pricing-table-wrapper").hide();
            }
          } else {
            $(".yaydp-pricing-table-wrapper").hide();
            $(
              ".yaydp-pricing-table-wrapper[data-applicable-variations ~= '" +
                variation.variation_id +
                "']"
            ).show();
          }

          $(
            "[data-variable='discount_value'], [data-variable='final_price'], [data-variable='discount_amount'], [data-variable='discounted_price']"
          ).each((index, item) => {
            const variation_price =
              discount_based_on === "regular_price"
                ? variation.display_regular_price
                : variation.display_price;
            const formula = $(item).data("formula");
            const final_price = eval(formula.replaceAll("x", variation_price));

            const formattedPrice = formatPrice(final_price);

            $(item).find(".woocommerce-Price-amount").html(formattedPrice);
          });
        }
      );
    }

    if (window.MutationObserver) {
      const observer = new MutationObserver(function (mutations) {
        mutations.forEach(function (mutation) {
          if (mutation.type === "childList") {
            mutation.addedNodes.forEach(function (node) {
              if (node.nodeType === 1) {
                const $node = $(node);
                if (
                  $node.hasClass("quick-view") ||
                  $node.hasClass("product-quickview") ||
                  $node.find(
                    ".quick-view, .product-quickview, .single_variation_wrap"
                  ).length > 0
                ) {
                  setTimeout(detectQuickViewOpen, 100);
                }
              }
            });
          }
        });
      });

      observer.observe(document.body, {
        childList: true,
        subtree: true,
      });
    }

    initializeVariationHandlers();
  });
})();
