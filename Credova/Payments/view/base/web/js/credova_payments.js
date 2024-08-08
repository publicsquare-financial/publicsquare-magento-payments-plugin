// Copyright © Credova Financial, LLC
//
// @package    Credova_Payments
// @version    4.0.8
define(
  [
    'credovajs'
  ],
  function (credovajs) {
    'use strict';

    // Warning: This file should be kept lightweight as it is loaded on nearly all pages.

    return (window.credova = {

      // Properties
      version: "4.0.8",
      credovaJs: null,
      cardElement: null,

      initElements: async function (params = {}, callback) {
        if (!this.credovaJs) {
          const _credova = await credovajs.init(params.apiKey)
          this.credovaJs = _credova
          this.cardElement = _credova.createCardElement({})
          this.cardElement.mount('#credova-elements-form')

          if (typeof callback === 'function') {
            callback(this)
          }
        }
      }
    });
  }
);
