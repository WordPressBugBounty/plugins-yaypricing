"use strict";
(function ($) {
  jQuery(document).ready(function ($) {
    var lastTriggeredEmail = null;

    // WooCommerce only refreshes the order review when address fields change,
    // so trigger it manually when the billing email changes to re-evaluate
    // rules using the billing email order count condition.
    $("form.checkout").on("change", 'input[name="billing_email"]', function () {
      var email = $(this).val().trim();
      if (email === lastTriggeredEmail) {
        return;
      }
      lastTriggeredEmail = email;
      $(document.body).trigger("update_checkout");
    });
  });
})(jQuery);
