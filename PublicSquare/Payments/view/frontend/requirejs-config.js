var config = {
    map: {
        '*': {
            'publicsquarejs': 'https://js.publicsquare.com',
            'publicsquare_payments': 'PublicSquare_Payments/js/publicsquare_payments',
        },
    },
    config: {
        mixins: {
            'Magento_Checkout/js/action/set-billing-address': {
                'PublicSquare_Payments/js/action/set-billing-address-mixin': true,
            },
        },
    },
};
