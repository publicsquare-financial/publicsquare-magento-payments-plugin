define([
           'mage/utils/wrapper',
           'Magento_Checkout/js/model/quote',
           'Magento_Customer/js/model/customer',
           'Magento_Customer/js/customer-data',
       ], function (wrapper) {
    'use strict';
    console.log('set-billing-address-mixin: loaded...');

    return function (originalFunction) {
        console.log('set-billing-address-mixin: Got target: %o', originalFunction);
        return wrapper.wrap(originalFunction, function setBillingAddressMixin(address) {
            console.log('set-billing-address-mixin: Got data: %o', address);
            // Always include billing address, even for virtual products
            // 🧠 If billing address is missing critical fields, auto-fill from customer defaults
            if ((!address || !address.firstname) && customer.isLoggedIn()) {
                const customerInfo = customerData.get('customer')();

                // Basic fallback — in real Magento, you'd pull full address from customerData.get('customer-addresses')
                const defaultBilling = {
                    firstname: customerInfo.firstname || 'Guest',
                    lastname: customerInfo.lastname || '',
                    street: [''],
                    city: '',
                    postcode: '',
                    telephone: '',
                    countryId: 'US'
                };

                console.log('set-billing-address-mixin: Using default billing address:', defaultBilling);
                address = defaultBilling;
            }

            // Ensure country always exists
            if (!address.countryId) {
                address.countryId = 'US';
            }
            // Call the original function
            console.log('set-billing-address-mixin: Passing address to original function');
            return originalFunction(address);
        });
    };
});
