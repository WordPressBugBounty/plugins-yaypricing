(function ($) {
  $(document).ready(function () {
    const QUANTITY_INPUT = 'form input[name="quantity"]';
    // Every layout marks its tiers ( table rows, radio labels, blocks, dropdown options ) with these attributes.
    const TIER = "[data-min-value]";
    const DROPDOWN = ".yaydp-pricing-dropdown";
    const TOOLTIP = ".yaydp-layout-tooltip";
    // Theme price elements the tooltip icon sits next to: classic templates and the Product Price block.
    // The block's inner price is re-rendered on variation change, so the icon goes beside it, never inside.
    const PRODUCT_PRICE = ".summary .price, .wp-block-woocommerce-product-price";

    // Displayed base price the tier formulas start from; null until a variation or YayExtra recomputes it.
    let currentBasePrice = null;

    function formulaHelpers() {
      return window.yaydp_pricing_formula ?? {};
    }

    function formatPrice(price) {
      const { formatPrice: format } = formulaHelpers();
      return format ? format(price) : String(price);
    }

    function getBasePrice($wrapper) {
      return currentBasePrice ?? parseFloat($wrapper.attr("data-base-price"));
    }

    function getTierPrice($tier, basePrice) {
      const formula = $tier.attr("data-price-formula");
      const { evaluate } = formulaHelpers();
      if (currentBasePrice !== null && formula && evaluate && !isNaN(basePrice)) {
        const price = parseFloat(evaluate(formula, basePrice));
        if (!isNaN(price)) {
          return Math.max(0, price);
        }
      }
      return parseFloat($tier.attr("data-price"));
    }

    function getSavingPercent(price, basePrice) {
      if (!(basePrice > 0) || isNaN(price)) {
        return 0;
      }
      return Math.max(0, Math.round(((basePrice - price) / basePrice) * 1000) / 10);
    }

    function tierContains(el, quantity) {
      const rowMin = $(el).data("minValue");
      const rowMax =
        typeof $(el).data("maxValue") !== "number"
          ? Number.MAX_SAFE_INTEGER
          : $(el).data("maxValue");
      return rowMin <= quantity && rowMax >= quantity;
    }

    // Savings and struck-through prices follow the current base price.
    function refreshTierPrices($wrapper) {
      const basePrice = getBasePrice($wrapper);
      $wrapper.find(TIER).each((_, el) => {
        const $tier = $(el);
        if (!$tier.attr("data-price-formula") && !$tier.attr("data-price")) {
          return;
        }
        const saving = getSavingPercent(getTierPrice($tier, basePrice), basePrice);
        $tier.find(".yaydp-pricing-tier__saving-value").text(`${saving}%`);
        $tier.find(".yaydp-pricing-tier__saving, .yaydp-pricing-tier__regular").prop("hidden", saving <= 0);
      });
    }

    // The dropdown toggle shows a copy of the selected tier; the copy drops the attributes the price scripts look for.
    function renderDropdownCurrent($wrapper, $selected) {
      const $current = $wrapper.find(".yaydp-pricing-dropdown__current");
      if (!$current.length) {
        return;
      }
      if (!$selected) {
        $current.children().not(".yaydp-pricing-dropdown__placeholder").remove();
        $current.find(".yaydp-pricing-dropdown__placeholder").prop("hidden", false);
        return;
      }
      const $copy = $selected.children().clone();
      $copy.find("[data-variable]").addBack("[data-variable]").removeAttr("data-variable data-formula");
      $current.children().not(".yaydp-pricing-dropdown__placeholder").remove();
      $current.find(".yaydp-pricing-dropdown__placeholder").prop("hidden", true);
      $current.append($copy);
    }

    function initSelectedRange() {
      const $input = $(QUANTITY_INPUT).first();
      if (!$input.length) {
        return;
      }
      const quantity = parseFloat($input.val());
      $(".yaydp-pricing-table-wrapper").each((_, wrapper) => {
        const $wrapper = $(wrapper);
        let selectedTier = null;
        $wrapper.find(TIER).each((_, el) => {
          $(el).removeClass("selected-range");
          if (selectedTier == null && tierContains(el, quantity)) {
            selectedTier = el;
          }
        });
        $wrapper.find(".yaydp-pricing-tier__radio").prop("checked", false);
        $wrapper.find("button.yaydp-pricing-tier").attr("aria-pressed", "false");
        $wrapper.find("[role='option']").attr("aria-selected", "false");
        $wrapper.find(".yaydp-pricing-tier__total-amount").text("");
        $wrapper.find(".yaydp-pricing-tier-summary").prop("hidden", true);
        if (selectedTier == null) {
          renderDropdownCurrent($wrapper, null);
          return;
        }

        const $selected = $(selectedTier);
        $selected.addClass("selected-range");
        $selected.find(".yaydp-pricing-tier__radio").prop("checked", true);
        if ($selected.is("button")) {
          $selected.attr("aria-pressed", "true");
        }
        if ($selected.is("[role='option']")) {
          $selected.attr("aria-selected", "true");
          $wrapper.find(".yaydp-pricing-tier-summary").prop("hidden", false);
          renderDropdownCurrent($wrapper, $selected);
        }
        const total = getTierPrice($selected, getBasePrice($wrapper)) * quantity;
        if (!isNaN(total)) {
          $wrapper
            .find(".yaydp-pricing-tier-summary .yaydp-pricing-tier__total-amount")
            .add($selected.find(".yaydp-pricing-tier__total-amount"))
            .text(formatPrice(total));
        }
      });
    }

    function setQuantity(minQuantity) {
      const $input = $(QUANTITY_INPUT).first();
      if (!$input.length) {
        return;
      }
      let quantity = Math.max(
        parseFloat(minQuantity) || 1,
        parseFloat($input.attr("min")) || 1
      );
      const maxQuantity = parseFloat($input.attr("max"));
      if (maxQuantity > 0 && quantity > maxQuantity) {
        quantity = maxQuantity;
      }
      $input.val(quantity).trigger("change");
    }

    /* Dropdown */

    function openDropdown($dropdown) {
      closeDropdowns($dropdown);
      $dropdown.addClass("is-open");
      $dropdown.find(".yaydp-pricing-dropdown__toggle").attr("aria-expanded", "true");
      $dropdown.find(".yaydp-pricing-dropdown__list").prop("hidden", false);
      const $options = $dropdown.find("[role='option']");
      const $selected = $options.filter("[aria-selected='true']");
      ($selected.length ? $selected : $options).first().trigger("focus");
    }

    function closeDropdowns(except, restoreFocus) {
      $(DROPDOWN + ".is-open")
        .not(except)
        .each((_, el) => {
          const $dropdown = $(el);
          $dropdown.removeClass("is-open");
          $dropdown.find(".yaydp-pricing-dropdown__list").prop("hidden", true);
          const $toggle = $dropdown.find(".yaydp-pricing-dropdown__toggle").attr("aria-expanded", "false");
          if (restoreFocus) {
            $toggle.trigger("focus");
          }
        });
    }

    function chooseOption($option) {
      setQuantity($option.attr("data-min-value"));
      closeDropdowns(null, true);
    }

    /* Tooltip */

    // Sit the tooltip icon next to the product price; it stays at the configured position otherwise.
    function placeTooltips() {
      $(TOOLTIP).each((_, el) => {
        const $wrapper = $(el);
        const $product = $wrapper.closest(".product, .type-product");
        const $price = ($product.length ? $product : $(document.body)).find(PRODUCT_PRICE).first();
        if ($price.length && !$price.closest(".yaydp-pricing-table-wrapper").length) {
          $price.addClass("yaydp-has-pricing-tooltip");
          $wrapper.addClass("is-inline").appendTo($price);
        }
      });
    }

    // Keep the popover inside the viewport.
    function positionPopover($wrapper) {
      const $popover = $wrapper.find(".yaydp-pricing-tooltip__popover");
      $popover.css("--yaydp-pt-shift", "0px");
      const rect = $popover[0]?.getBoundingClientRect();
      if (!rect || !rect.width) {
        return;
      }
      const gutter = 16;
      let shift = 0;
      if (rect.right > window.innerWidth - gutter) {
        shift = window.innerWidth - gutter - rect.right;
      }
      if (rect.left + shift < gutter) {
        shift = gutter - rect.left;
      }
      $popover.css("--yaydp-pt-shift", `${shift}px`);
    }

    // "dismiss" also hides a popover kept open by hover or focus, until the pointer or focus leaves.
    function closeTooltips(except, dismiss) {
      const $open = $(TOOLTIP).not(except);
      $open.removeClass("is-open").find(".yaydp-pricing-tooltip__trigger").attr("aria-expanded", "false");
      if (dismiss) {
        $open.addClass("is-dismissed");
      }
    }

    placeTooltips();
    $(".yaydp-pricing-table-wrapper").each((_, wrapper) => refreshTierPrices($(wrapper)));
    initSelectedRange();

    $(document.body).on("change input", QUANTITY_INPUT, initSelectedRange);

    $(document.body).on("yaydp_formula_prices_updated", function (event, basePrice) {
      currentBasePrice = parseFloat(basePrice);
      $(".yaydp-pricing-table-wrapper").each((_, wrapper) => refreshTierPrices($(wrapper)));
      initSelectedRange();
    });

    $(document.body).on("change", ".yaydp-pricing-tier__radio", function () {
      setQuantity($(this).closest(TIER).attr("data-min-value"));
    });

    $(document.body).on("click", "button.yaydp-pricing-tier", function () {
      setQuantity($(this).attr("data-min-value"));
    });

    $(document.body).on("click", ".yaydp-pricing-dropdown__toggle", function () {
      const $dropdown = $(this).closest(DROPDOWN);
      if ($dropdown.hasClass("is-open")) {
        closeDropdowns();
      } else {
        openDropdown($dropdown);
      }
    });

    $(document.body).on("keydown", ".yaydp-pricing-dropdown__toggle", function (event) {
      if (["ArrowDown", "ArrowUp"].includes(event.key)) {
        event.preventDefault();
        openDropdown($(this).closest(DROPDOWN));
      }
    });

    $(document.body).on("click", DROPDOWN + " [role='option']", function () {
      chooseOption($(this));
    });

    $(document.body).on("keydown", DROPDOWN + " [role='option']", function (event) {
      const $option = $(this);
      const $options = $option.closest("[role='listbox']").find("[role='option']");
      const index = $options.index($option);
      const moves = { ArrowDown: index + 1, ArrowUp: index - 1, Home: 0, End: $options.length - 1 };
      if (event.key in moves) {
        event.preventDefault();
        $options.eq(Math.min(Math.max(moves[event.key], 0), $options.length - 1)).trigger("focus");
      } else if (event.key === "Enter" || event.key === " ") {
        event.preventDefault();
        chooseOption($option);
      } else if (event.key === "Tab") {
        closeDropdowns();
      }
    });

    $(document.body).on("mouseenter focusin", TOOLTIP, function () {
      positionPopover($(this));
    });

    $(document.body).on("mouseleave focusout", TOOLTIP, function (event) {
      if (event.type === "focusout" && this.contains(event.relatedTarget)) {
        return;
      }
      $(this).removeClass("is-dismissed");
    });

    $(document.body).on("click", ".yaydp-pricing-tooltip__trigger", function (event) {
      // The icon can sit inside a link ( e.g. a price in a block theme ); never follow it.
      event.preventDefault();
      const $wrapper = $(this).closest(TOOLTIP);
      const isOpen = !$wrapper.hasClass("is-open");
      closeTooltips($wrapper);
      $wrapper.toggleClass("is-open", isOpen).toggleClass("is-dismissed", !isOpen);
      $(this).attr("aria-expanded", isOpen ? "true" : "false");
      positionPopover($wrapper);
    });

    $(document).on("click", function (event) {
      const $target = $(event.target);
      if (!$target.closest(TOOLTIP).length) {
        closeTooltips();
      }
      if (!$target.closest(DROPDOWN).length) {
        closeDropdowns();
      }
    });

    $(document).on("keydown", function (event) {
      if (event.key === "Escape") {
        closeTooltips(null, true);
        closeDropdowns(null, $(event.target).closest(DROPDOWN).length > 0);
      }
    });
  });
})(window.jQuery);
