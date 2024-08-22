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
        'Magento_Checkout/js/view/payment/default',
        'Credova_Payments/js/credova_payments',
        'Magento_Checkout/js/model/url-builder',
        'mage/storage',
        'Magento_Checkout/js/model/quote',
        'Magento_Checkout/js/model/payment/additional-validators',
        'Magento_Checkout/js/model/full-screen-loader',
        'mage/translate'
    ],
    function ($, Component, credova, urlBuilder, storage, quote, additionalValidators, fullScreenLoader, $t) {
        'use strict';

        return Component.extend({
            defaults: {
                template: 'Credova_Payments/payment/credova_payments'
            },
            apiKey: window.checkoutConfig.payment.credova_payments.pk,
            elementsFormSelector: '#credova-elements-form',
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
                        cardId
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
            }
        });
    });

