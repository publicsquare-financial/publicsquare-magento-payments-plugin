var config = {
    map: {
        '*': {
            'publicsquarejs': 'https://js.publicsquare.com',
            'publicsquare_payments': 'PublicSquare_Payments/js/publicsquare_payments',
            'publicsquare_admin': 'PublicSquare_Payments/js/publicsquare_admin',
        },
    },
    config: {
        mixins: {
            'mage/validation': {
                'PublicSquare_Payments/js/admin-config/validator-rules-mixin': true,
            },
        },
    },
};
