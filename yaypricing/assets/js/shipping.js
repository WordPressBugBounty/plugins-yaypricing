"use strict";
(function ($) {
  jQuery(document).ready(function ($) {
    //Update cart when change payment method checkout page
    $(document.body).on("updated_shipping_method", function () {
      $(document.body).trigger("update_checkout");
      $(document.body).trigger("wc_update_cart");
    });
  });
})(jQuery);
