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
    ['Magento_Checkout/js/view/payment/default'],
    function (Component) {
        'use strict';

        return Component.extend({
            defaults: {
                template: 'Credova_Payments/payment/credovapayments',
                preQualificationId: null
            }
        });
    });

