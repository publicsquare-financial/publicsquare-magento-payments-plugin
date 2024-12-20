require(
    [
        'uiComponent',
        'Magento_Checkout/js/model/payment/renderer-list'
    ],
    function (
        Component,
        rendererList
    ) {
        'use strict';
        rendererList.push(
            {
                type: 'publicsquare_payments',
                component: 'PublicSquare_Payments/js/payment-method'
            }
        );
        /**
    * Add view logic here if needed
    */
        return Component.extend({});
    }
);