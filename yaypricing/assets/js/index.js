(function ($) {
  $(document).ready(function () {
    if (!window.elementorFrontend || !window.elementorFrontend.getElements) {
      return;
    }
    const menuCarts = window.elementorFrontend
      ?.getElements()
      ?.$body?.find(".elementor-widget-woocommerce-menu-cart");
    menuCarts.each((_, el) => {
      const settings = $(el).data("settings");
      if (!settings) {
        return;
      }
      if (settings.automatically_open_cart == null) {
        return;
      }
      const currentValue = settings.automatically_open_cart;
      settings.automatically_open_cart = "no";
      $(el).data("settings", settings);
      setTimeout(() => {
        settings.automatically_open_cart = currentValue;
        $(el).data("settings", settings);
      }, 1000);
    });
  });
})(window.jQuery);
