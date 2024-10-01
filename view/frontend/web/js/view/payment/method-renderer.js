/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
/*browser:true*/
/*global define*/
define([
  'uiComponent',
  'Magento_Checkout/js/model/payment/renderer-list',
], function (Component, rendererList) {
  'use strict';

  const component =
    'Fiserv_Checkout/js/view/payment/method-renderer/fisrv_generic';

  const renderObjects = [
    {
      type: 'fisrv_generic',
      component,
    },
    {
      type: 'fisrv_creditcard',
      component,
    },
    {
      type: 'fisrv_applepay',
      component,
    },
    {
      type: 'fisrv_googlepay',
      component,
    },
  ];

  rendererList.push(...renderObjects);
  return Component.extend({});
});
