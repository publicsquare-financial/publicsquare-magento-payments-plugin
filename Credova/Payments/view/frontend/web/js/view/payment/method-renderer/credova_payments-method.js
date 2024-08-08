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
        'Credova_Payments/js/credova_payments'
    ],
    function (Component, credova) {
        'use strict';

        return Component.extend({
            defaults: {
                template: 'Credova_Payments/payment/credova_payments'
            },
            onContainerRendered: async function () {
                // This runs when the container div on the checkout page renders
                credova.initElements({ apiKey: '{api_key}' }, () => { })
            }
        });
    });

