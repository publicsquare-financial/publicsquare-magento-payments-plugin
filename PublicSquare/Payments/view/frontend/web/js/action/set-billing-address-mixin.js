define([], function () {
    'use strict';
    console.log('set-billing-address-mixin: loaded...');

    return function (target) {
        console.log('set-billing-address-mixin: Got target: %o', target);
        return function (data) {
            console.log('set-billing-address-mixin: Got data: %o', data);
            // Always include billing address, even for virtual products
            if (!data.billingAddress && window.checkoutConfig && window.checkoutConfig.billingAddressFromData) {
                data.billingAddress = window.checkoutConfig.billingAddressFromData;
            }
            // Call the original function
            return target(data);
        };
    };
});
