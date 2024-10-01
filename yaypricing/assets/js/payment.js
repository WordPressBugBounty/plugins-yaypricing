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
  });
})(jQuery);