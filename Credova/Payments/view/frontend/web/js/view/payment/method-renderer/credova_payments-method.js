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
        'Magento_Checkout/js/view/payment/default',
        'Credova_Payments/js/credova_payments',
        'Magento_Checkout/js/model/url-builder',
        'mage/storage',
        'Magento_Checkout/js/model/quote',
        'Magento_Checkout/js/model/payment/additional-validators',
        'Magento_Checkout/js/model/full-screen-loader',
        'mage/translate'
    ],
    function (Component, credova, urlBuilder, storage, quote, additionalValidators, fullScreenLoader, $t) {
        'use strict';

        return Component.extend({
            defaults: {
                template: 'Credova_Payments/payment/credova_payments'
            },
            apiKey: null,
            elementsFormSelector: '#credova-elements-form',
            onContainerRendered: function () {
                this.apiKey = 'pk_test_PZ1VnynckiokKR2TyZ8wCY'
                // This runs when the container div on the checkout page renders
                credova.initElements({
                    apiKey: this.apiKey,
                    selector: this.elementsFormSelector
                }, () => { })
            },
            createCard: function (cardholder_name) {
                console.log('calling createCard', credova, cardholder_name, credova.cardElement)
                return credova.createCard(cardholder_name, credova.cardElement)
            },
            placeOrderCredovaPayments: async function () {
                fullScreenLoader.startLoader()
                if (this.validate() && additionalValidators.validate()) {
                    const url = urlBuilder.createUrl('/credova_payments/payments', {})
                    const billingAddress = quote.billingAddress();
                    console.log(url, billingAddress)
                    try {
                        const card = await this.createCard(
                            `${billingAddress.firstname} ${billingAddress.lastname}`
                        )
                        console.log(card)
                        fullScreenLoader.stopLoader()

                        const response = await storage.post(url, JSON.stringify({
                            // customer: {
                            //     first_name: billingAddress.firstname,
                            //     last_name: billingAddress.lastname,
                            //     phone_number: billingAddress.telephone,
                            //     email: billingAddress.guestEmail
                            // }
                            card: card.id
                        }), false)
                        console.log(response)
                        return true
                    } catch (error) {
                        console.log(error)
                        messageList.addErrorMessage({
                            message: $t(error)
                        });
                    }
                } else {
                    messageList.addErrorMessage({
                        message: $t('Please check your checkout details.')
                    });
                }
                fullScreenLoader.stopLoader();
                return false
            }
        });
    });

