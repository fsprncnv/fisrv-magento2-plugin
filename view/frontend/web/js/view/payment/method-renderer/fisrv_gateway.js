/*browser:true*/
/*global define*/
define([
  'Magento_Checkout/js/view/payment/default',
  'jquery',
  'mage/url',
], function (Component, $, url) {
  'use strict';

  return Component.extend({
    defaults: {
      redirectAfterPlaceOrder: false,
      code: 'fisrv_gateway',
      template: 'Fisrv_Payment/payment/form',
    },

    getCode: function () {
      return 'fisrv_gateway';
    },

    afterPlaceOrder: function () {
      window.location.replace(url.build('fisrv/checkout/redirectaction/'));
    },

    // getData: function () {
    //   return {
    //     method: this.item.method,
    //     additional_data: {
    //       transaction_result: this.transactionResult(),
    //     },
    //   };
    // },

    // placeOrder: function ($input) {
    //   console.log('Triggered placeOrder at ' + new Date().toLocaleTimeString());

    //   console.log($input);

    //   $.ajax({
    //     showLoader: true,
    //     url: window.location.replace(url.build('fisrv/checkout/redirects/')),
    //     type: 'POST',
    //     data: 'Request',
    //     dataType: 'json',
    //   }).done(function () {
    //     console.log('Executed AJAX redirect');
    //   });
    // },

    validate: function () {
      return true;
    },
  });
});
