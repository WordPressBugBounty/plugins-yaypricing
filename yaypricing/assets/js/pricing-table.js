(function ($) {
  $(document).ready(function () {
    function initSelectedRange(input) {
      if (!input) {
        return;
      }
      const value = $(input).val();
      $(".yaydp-pricing-table").each((_, wrapper) => {
        let selectedRow = null;
        $(wrapper)
          .find("tbody tr")
          .each((_, el) => {
            $(el).removeClass("selected-range");
            if (selectedRow != null) {
              return;
            }
            const rowMin = $(el).data("minValue");
            const rowMax =
              typeof $(el).data("maxValue") !== "number"
                ? Number.MAX_SAFE_INTEGER
                : $(el).data("maxValue");
            if (rowMin <= value && rowMax >= value) {
              selectedRow = el;
            }
          });
        if (selectedRow == null) {
          return;
        }

        $(selectedRow).addClass("selected-range");
      });
    }
    initSelectedRange($('form input[name="quantity"]'));
    $(document.body).on("change", 'form input[name="quantity"]', function () {
      initSelectedRange(this);

      //   const localizeData = window.yaydp_frontend_data ?? {};
      //   const discount_based_on = localizeData.discount_based_on ?? "regular_price";

      //   $(".single_variation_wrap").on(
      // 	"show_variation",
      // 	function (event, variation) {
      // 	  $("#yaydp-offer-description").show();
      // 	  $(".yaydp-pricing-table-wrapper").show();
      // 	  $(
      // 		"[data-variable='discount_value'], [data-variable='final_price'], [data-variable='discount_amount'], [data-variable='discounted_price']"
      // 	  ).each((index, item) => {
      // 		const currency = $(item)
      // 		  .find(".woocommerce-Price-currencySymbol")
      // 		  .first()
      // 		  .html();
      // 		const variation_price =
      // 		  discount_based_on === "regular_price"
      // 			? variation.display_regular_price
      // 			: variation.display_price;
      // 		const formula = $(item).data("formula");
      // 		const final_price = eval(formula.replaceAll("x", variation_price));
      // 		$(item)
      // 		  .find(".woocommerce-Price-amount")
      // 		  .html(
      // 			`<span class="woocommerce-Price-currencySymbol">${currency}</span>${isNaN(final_price) ? final_price : final_price.toFixed(2)}`
      // 		  );
      // 	  });
      // 	}
      //   );
    });
  });
})(window.jQuery);
