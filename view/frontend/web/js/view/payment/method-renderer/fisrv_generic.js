define(['Magento_Checkout/js/view/payment/default', 'mage/url'], function (
  Component,
  url
) {
  'use strict';

  return Component.extend({
    defaults: {
      redirectAfterPlaceOrder: false,
      template: 'Fisrv_Payment/payment/default',
    },

    afterPlaceOrder: function () {
      window.location.replace(url.build('fisrv/checkout/redirectaction/'));
    },

    validate: function () {
      return true;
    },

    placeOrder: function (data, event) {
      var self = this;

      if (event) {
        event.preventDefault();
      }

      if (this.validate()) {
        self.afterPlaceOrder();
        return true;
      }

      return false;
    },
  });
});
