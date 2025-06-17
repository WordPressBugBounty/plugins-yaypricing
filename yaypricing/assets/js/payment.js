"use strict";
(function ($) {
  jQuery(document).ready(function ($) {
    //Update cart when change payment method checkout page
    $("form.checkout").on(
      "change",
      'input[name="payment_method"]',
      function () {
        $(document.body).trigger("update_checkout");
      }
    );

    $(
      ".wc-block-components-shipping-rates-control .wc-block-components-radio-control__input"
    ).each(function () {
      const rateId = $(this).val();
      if ($(this).is(":checked")) {
        window.wp.data.dispatch("wc/store/cart").selectShippingRate(rateId);
      }
    });

    // if (window.german_market_price_variable_products != null) {
    //   return;
    // }

    // $(document).on(
    //   "change",
    //   '[name="radio-control-wc-payment-method-options"]',
    //   function () {
    //     window.wp.data.dispatch("wc/store/cart").updateCustomerData({
    //       paymentMethod: $(this).val(),
    //     });
    //   }
    // );
  });
})(jQuery);
