/**
 * Credova Financial method
 *
 * @category  Credova
 * @package   Credova_Payments
 * @author    Credova <info@credova.com>
 * @copyright 2024 Credova
 * @license   http://opensource.org/licenses/osl-3.0.php (OSL 3.0)
 * @link https://credova.com/
 */
/* @api */
define(
    [
        'jquery',
        'Magento_Payment/js/view/payment/cc-form',
        'Credova_Payments/js/credova_payments',
        'Magento_Checkout/js/model/url-builder',
        'mage/storage',
        'Magento_Checkout/js/model/quote',
        'Magento_Checkout/js/model/payment/additional-validators',
        'Magento_Checkout/js/model/full-screen-loader',
        'mage/translate',
        'Magento_Vault/js/view/payment/vault-enabler',
        'Magento_Ui/js/model/messageList',
    ],
    function ($, Component, credova, urlBuilder, storage, quote, additionalValidators, fullScreenLoader, $t, VaultEnabler, messageList) {
        'use strict';

        return Component.extend({
            defaults: {
                template: 'Credova_Payments/payment/credova_payments',
                paymentPayload: {
                    nonce: null
                },
                apiKey: window.checkoutConfig.payment.credova_payments.pk,
                code: 'credova_payments',
                elementsFormSelector: '#credova-elements-form',
                vaultName: 'credova_payments'
            },
            initialize: function () {
                console.log('initialize')
                var self = this;
                self._super();
                this.vaultEnabler = new VaultEnabler();
                this.vaultEnabler.setPaymentCode(this.getVaultCode());
                return self
            },
            onContainerRendered: function () {
                // This runs when the container div on the checkout page renders
                credova.initElements({
                    apiKey: this.apiKey,
                    selector: this.elementsFormSelector
                }, () => { })
            },
            createCard: function (cardholder_name) {
                return credova.createCard(cardholder_name, credova.cardElement)
            },
            placeOrder: async function () {
                const self = this
                fullScreenLoader.startLoader()
                if (this.validate() && additionalValidators.validate()) {
                    const billingAddress = quote.billingAddress();
                    try {
                        // Tokenize the card in Credova
                        const card = await this.createCard(
                            `${billingAddress.firstname} ${billingAddress.lastname}`
                        )
                        // Submit the payment
                        await self.placeOrderWithCardId(card.id)
                    } catch (error) {
                        console.log(error)
                        fullScreenLoader.stopLoader();
                        messageList.addErrorMessage({
                            message: $t(error)
                        });
                    }
                } else {
                    messageList.addErrorMessage({
                        message: $t('Please check your checkout details.')
                    });
                    return false
                }
            },
            placeOrderWithCardId: function (cardId) {
                var serviceUrl = urlBuilder.createUrl('/credova_payments/payments', {});

                return storage.post(
                    serviceUrl,
                    JSON.stringify({
                        cardId,
                        saveCard: true
                    })
                ).done(function (response) {
                    // Handle successful order placement
                    const maskId = window.checkoutConfig.quoteData.entity_id;
                    const successUrl = `${window.checkoutConfig.payment.credova_payments.successUrl}?${window.checkoutConfig.isCustomerLoggedIn ? 'refercust' : 'refergues'}=${maskId}`
                    $.mage.redirect(successUrl);
                }).fail(function (response) {
                    errorProcessor.process(response);
                }).always(function () {
                    fullScreenLoader.stopLoader();
                });
            },
            /**
             * @returns {Object}
             */
            getData: function () {
                var data = {
                    'method': this.getCode(),
                    'additional_data': {
                        'payment_method_nonce': this.paymentPayload.nonce
                    }
                };

                data['additional_data'] = _.extend(data['additional_data'], this.additionalData);
                this.vaultEnabler.visitAdditionalData(data);

                return data;
            },
            /**
             * @returns {Boolean}
             */
            isVaultEnabled: function () {
                console.log('isVaultEnabled', this.vaultEnabler, this.vaultEnabler.isVaultEnabled());
                return this.vaultEnabler.isVaultEnabled();
            },
            /**
             * Returns vault code.
             *
             * @returns {String}
             */
            getVaultCode: function () {
                console.log('getVaultCode', window.checkoutConfig.payment[this.getCode()].ccVaultCode);
                return window.checkoutConfig.payment[this.getCode()].ccVaultCode;
            },
            /**
             * Return Payment method code
             *
             * @returns {*}
             */
            getCode: function () {
                return this.code;
            }

        });
    });

