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
  'mage/translate'
], function ($, VaultComponent, messageList, fullScreenLoader, urlBuilder, storage, $t) {
  'use strict';

  return VaultComponent.extend({
    defaults: {
      template: 'Magento_Vault/payment/form',
      modules: {
        hostedFields: '${ $.parentName }.publicsquare_payments'
      },
      additionalData: {}
    },

    /**
     * Get PayPal payer email
     * @returns {String}
     */
    getPayerEmail: function () {
      return this.details.payerEmail;
    },

    /**
     * Get type of payment
     * @returns {String}
     */
    getPaymentIcon: function () {
      return window.checkoutConfig.payment['braintree_paypal'].paymentIcon;
    },

    getIcons: function (type) {
      return 'https://placehold.it/50x50';
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
      var serviceUrl = urlBuilder.createUrl('/publicsquare_payments/payments', {});

      return storage.post(
        serviceUrl,
        JSON.stringify({
          cardId: null,
          saveCard: false,
          publicHash,
        })
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
        'method': this.code,
        'additional_data': {
          'public_hash': this.publicHash
        }
      };

      data['additional_data'] = _.extend(data['additional_data'], this.additionalData);

      return data;
    }
  });
});
