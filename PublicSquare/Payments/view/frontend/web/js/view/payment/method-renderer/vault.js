/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
/*browser:true*/
/*global define*/
define([
  'jquery',
  'Magento_Vault/js/view/payment/method-renderer/vault',
  'Magento_Ui/js/model/messageList',
  'Magento_Checkout/js/model/full-screen-loader',
  'Magento_Checkout/js/model/url-builder',
  'mage/storage',
  'mage/translate',
  "Magento_Customer/js/model/customer",
  "Magento_Checkout/js/model/place-order",
  "Magento_Checkout/js/model/quote",
], function ($, VaultComponent, messageList, fullScreenLoader, urlBuilder, storage, $t, customer, placeOrderService, quote) {
  'use strict';

  return VaultComponent.extend({
    defaults: {
      template: 'Magento_Vault/payment/form',
      modules: {
        hostedFields: '${ $.parentName }.publicsquare_payments'
      },
      additionalData: {},
      idempotencyKey: null,
    },

    initialize: function () {
      var self = this;
      self._super();
      self.idempotencyKey = self.generateIdempotencyKey();
      return self;
    },


    getIcons: function (type) {
      return {
        url: `${window.checkoutConfig.payment.publicsquare_payments.cardImagesBasePath}${type}.svg`,
        width: '45',
        height: '29',
      };
    },

    /**
     * Get last 4 digits of card
     * @returns {String}
     */
    getMaskedCard: function () {
      return this.details.maskedCC;
    },

    /**
     * Get expiration date
     * @returns {String}
     */
    getExpirationDate: function () {
      return this.details.expirationDate;
    },

    /**
     * Get card type
     * @returns {String}
     */
    getCardType: function () {
      return this.details.type;
    },

      /**
       * Place order
       */
    placeOrder: function () {
      var self = this;

      self.hostedFields(() => {
        self.placeOrderWithCardId(self.publicHash);
      })
    },

    placeOrderWithCardId: function (publicHash) {
      fullScreenLoader.startLoader();
      var serviceUrl = urlBuilder.createUrl(
        customer.isLoggedIn() ?
          '/carts/mine/payment-information' :
          '/guest-carts/:quoteId/payment-information',
        {
          quoteId: quote.getQuoteId()
        }
      );

      return placeOrderService(
        serviceUrl,
        {
          ...(!customer.isLoggedIn() && { email: quote.guestEmail }),
          paymentMethod: this.getData()
        },
        messageList
      ).done(function (response) {
        // Handle successful order placement
        const maskId = window.checkoutConfig.quoteData.entity_id;
        const successUrl = `${window.checkoutConfig.payment.publicsquare_payments.successUrl}?${window.checkoutConfig.isCustomerLoggedIn ? 'refercust' : 'refergues'}=${maskId}`;
        $.mage.redirect(successUrl);
      }).fail(function (response) {
        messageList.addErrorMessage({
          message: $t('Something went wrong. Please try again or contact support for assistance.')
        });
      }).always(function () {
        fullScreenLoader.stopLoader();
      });
    },

      /**
       * Get payment method data
       * @returns {Object}
       */
    getData: function () {
      var data = {
        method: this.code,
        additional_data: {
          public_hash: this.publicHash,
          idempotencyKey: this.idempotencyKey,
        }
      };

      data['additional_data'] = _.extend(data['additional_data'], this.additionalData);

      return data;
    },

    generateIdempotencyKey() {
      const timestamp = Date.now().toString();
      const random = Math.random().toString(36).substr(2, 9);
      return timestamp + random;
    },
  });
});
