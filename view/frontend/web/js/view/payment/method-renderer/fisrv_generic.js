define(['Magento_Checkout/js/view/payment/default', 'mage/url'], function (
  Component,
  url
) {
  'use strict';

  const checkoutConfig = window.checkoutConfig.payment;

  return Component.extend({
    redirectAfterPlaceOrder: false,

    defaults: {
      template: 'Fiserv_Checkout/payment/default',
    },

    afterPlaceOrder: function () {
      window.location.replace(url.build('fisrv/checkout/redirectaction/'));
    },

    placeOrder: function (data, event) {
      var self = this;

      if (event) {
        event.preventDefault();
      }

      if (this.validate() && this.isPlaceOrderActionAllowed() === true) {
        this.isPlaceOrderActionAllowed(false);

        this.getPlaceOrderDeferredObject()
          .done(function () {
            self.afterPlaceOrder();
            if (self.redirectAfterPlaceOrder) {
              redirectOnSuccessAction.execute();
            }
          })
          .always(function () {
            self.isPlaceOrderActionAllowed(true);
          });

        return true;
      }

      return false;
    },

    validate: function () {
      return true;
    },

    getConfigData: function () {
      if (this.isPlaceOrderActionAllowed()) {
        return 'You will be redirected to an external checkout page.';
      }

      return 'Sorry, Fiserv checkout is currently not available. Contact admin of the store to enable this method.'
    },

    isPlaceOrderActionAllowed: function () {
      return checkoutConfig.fisrv_gateway.is_available
    },

    getLogo: function () {
      return checkoutConfig.fisrv_gateway['fisrv_generic'].logo;
    }

  });
});
