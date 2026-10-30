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

    /**
     * Recompute every formula-driven price ( pricing table, offer description ) for the given base price.
     */
    function evaluateFormula(formula, basePrice) {
      return eval(String(formula).replaceAll("x", basePrice));
    }

    // Shared with pricing-table.js, which recomputes tier savings and totals from the same formulas.
    window.yaydp_pricing_formula = {
      formatPrice,
      evaluate: evaluateFormula,
    };

    function updateFormulaPrices(basePrice) {
      if (isNaN(basePrice)) {
        return;
      }
      $(
        "[data-variable='discount_value'], [data-variable='final_price'], [data-variable='discount_amount'], [data-variable='discounted_price'], [data-variable='base_price']"
      ).each((index, item) => {
        const formula = $(item).attr("data-formula");
        if (!formula) {
          return;
        }
        const final_price = evaluateFormula(formula, basePrice);
        $(item).find(".woocommerce-Price-amount").html(formatPrice(final_price));
      });
      $(document.body).trigger("yaydp_formula_prices_updated", [basePrice]);
    }

    // Displayed price the formulas start from: the product's own, or the selected variation's.
    let currentBasePrice = parseFloat(
      $(".yaydp-pricing-table-wrapper").first().attr("data-base-price")
    );

    /**
     * How much the selected YayExtra options scale the base price, 1 without YayExtra.
     *
     * YayExtra keeps the unit price with options in `data-token-product-price-replace` of its
     * `.yaye-product-price` input ( present when its "Update product price" setting is on ). Its prices
     * may exclude tax while ours are displayed, so apply the option cost as a ratio of its own price.
     */
    function getYayExtraPriceRatio() {
      const $yayePrice = $(".yaye-product-price");
      if (!$yayePrice.length) {
        return 1;
      }
      const price = parseFloat($yayePrice.attr("data-product-price"));
      const regularPrice = parseFloat($yayePrice.attr("data-product-regular-price"));
      const optionCost =
        parseFloat($yayePrice.attr("data-token-product-price-replace")) - price;
      const basePrice =
        discount_based_on === "regular_price" && regularPrice > 0
          ? regularPrice
          : price;
      if (!(basePrice > 0) || isNaN(optionCost)) {
        return 1;
      }
      return (basePrice + optionCost) / basePrice;
    }

    // YayExtra fires this whenever its options ( size, colour, ... ) change the product price.
    if (window.wp?.hooks?.addFilter) {
      window.wp.hooks.addFilter(
        "yaye_product_price_hook",
        "yaydp/pricing-table",
        function (html) {
          updateFormulaPrices(currentBasePrice * getYayExtraPriceRatio());
          return html;
        }
      );
    }

    function updateSaleTagRules($saleTag, ruleNames) {
      if (!$saleTag.length || !ruleNames || ruleNames.length === 0) {
        return;
      }

      let saleTagTemplate = $saleTag.attr("data-sale-tag-template");
      let discountAmount = $saleTag.attr("data-discount-amount");

      if (!saleTagTemplate) {
        saleTagTemplate = $saleTag.data("sale-tag-template");
      }
      if (!discountAmount) {
        discountAmount = $saleTag.data("discount-amount");
      }

      if (!saleTagTemplate) {
        return;
      }

      let saleText = saleTagTemplate;

      if (discountAmount && discountAmount > 0) {
        saleText = saleText.replace(/\{amount\}/g, discountAmount + "%");
      }

      const ruleNamesText = ruleNames.join(", ");
      saleText = saleText.replace(/\{rule_name\}/g, ruleNamesText);

      $saleTag.text(saleText);
    }

    function restoreSaleTagToInitial($saleTag) {
      if (!$saleTag.length) {
        return;
      }

      let initialRules = $saleTag.attr("data-initial-rules");
      if (initialRules) {
        try {
          initialRules = JSON.parse(initialRules);
        } catch (e) {
          initialRules = $saleTag.data("initial-rules");
        }
      } else {
        initialRules = $saleTag.data("initial-rules");
      }

      if (
        initialRules &&
        Array.isArray(initialRules) &&
        initialRules.length > 0
      ) {
        updateSaleTagRules($saleTag, initialRules);
      } else {
        let variationRulesData = $saleTag.attr("data-variation-rules");
        if (variationRulesData) {
          try {
            variationRulesData = JSON.parse(variationRulesData);
          } catch (e) {
            variationRulesData = $saleTag.data("variation-rules");
          }
        } else {
          variationRulesData = $saleTag.data("variation-rules");
        }

        if (variationRulesData && typeof variationRulesData === "object") {
          const allRuleNames = [];
          Object.keys(variationRulesData).forEach(function (variationId) {
            const rules = variationRulesData[variationId];
            if (Array.isArray(rules)) {
              rules.forEach(function (ruleName) {
                if (allRuleNames.indexOf(ruleName) === -1) {
                  allRuleNames.push(ruleName);
                }
              });
            }
          });

          if (allRuleNames.length > 0) {
            updateSaleTagRules($saleTag, allRuleNames);
          }
        }
      }
    }

    $(".single_variation_wrap").on(
      "show_variation",
      function (event, variation) {
        $(".yaydp-pricing-table-wrapper").hide();
        const variationId = String(variation.variation_id);
        let lastMatchingTable = null;
        $(".yaydp-pricing-table-wrapper").each(function () {
          const applicableVariationsAttr = $(this).attr(
            "data-applicable-variations"
          );
          const applicableVariations = applicableVariationsAttr
            .split(",")
            .map((item) => item.trim());
          if (applicableVariations.includes(variationId)) {
            lastMatchingTable = $(this);
          }
        });
        if (lastMatchingTable) {
          lastMatchingTable.show();
        }
        const variation_price =
          discount_based_on === "regular_price"
            ? variation.display_regular_price
            : variation.display_price;
        currentBasePrice = parseFloat(variation_price);
        updateFormulaPrices(currentBasePrice * getYayExtraPriceRatio());

        const $saleTag = $(".yaydp-sale-tag[data-variable-product='1']");
        if ($saleTag.length) {
          let variationRulesData = $saleTag.attr("data-variation-rules");
          if (variationRulesData) {
            try {
              variationRulesData = JSON.parse(variationRulesData);
            } catch (e) {
              variationRulesData = $saleTag.data("variation-rules");
            }
          } else {
            variationRulesData = $saleTag.data("variation-rules");
          }

          if (variationRulesData && typeof variationRulesData === "object") {
            let ruleNames =
              variationRulesData[variationId] ||
              variationRulesData[parseInt(variationId, 10)];

            if (!ruleNames) {
              Object.keys(variationRulesData).forEach(function (key) {
                if (
                  String(key) === variationId ||
                  parseInt(key, 10) === parseInt(variationId, 10)
                ) {
                  ruleNames = variationRulesData[key];
                }
              });
            }

            if (ruleNames && Array.isArray(ruleNames) && ruleNames.length > 0) {
              updateSaleTagRules($saleTag, ruleNames);
            }
          }
        }
      }
    );
    $(".single_variation_wrap").on("hide_variation", function (event) {
      $(".yaydp-pricing-table-wrapper").hide();

      setTimeout(function () {
        const $saleTag = $(".yaydp-sale-tag[data-variable-product='1']");
        if ($saleTag.length) {
          restoreSaleTagToInitial($saleTag);
        }
      }, 50);
    });

    function areAllVariationSelectsEmpty() {
      let allEmpty = true;
      $("form.variations_form select").each(function () {
        if ($(this).val() && $(this).val() !== "") {
          allEmpty = false;
          return false;
        }
      });
      return allEmpty;
    }

    $(document).on("change", "form.variations_form select", function () {
      setTimeout(function () {
        const $saleTag = $(".yaydp-sale-tag[data-variable-product='1']");
        if ($saleTag.length && areAllVariationSelectsEmpty()) {
          restoreSaleTagToInitial($saleTag);
        }
      }, 100);
    });

    $(document.body).on("reset_data", function () {
      setTimeout(function () {
        const $saleTag = $(".yaydp-sale-tag[data-variable-product='1']");
        if ($saleTag.length) {
          restoreSaleTagToInitial($saleTag);
        }
      }, 100);
    });

    $(document).on("reset", "form.variations_form", function () {
      setTimeout(function () {
        const $saleTag = $(".yaydp-sale-tag[data-variable-product='1']");
        if ($saleTag.length) {
          restoreSaleTagToInitial($saleTag);
        }
      }, 100);
    });
  });
})(jQuery);
