define(
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
                type: 'credovapayments',
                component: 'Credova_Payments/js/view/payment/method-renderer/credovapayments-method'
            }
        );
        /**
    * Add view logic here if needed
    */
        return Component.extend({});
    }
);