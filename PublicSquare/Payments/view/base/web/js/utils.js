define([
           'Magento_Checkout/js/model/url-builder',
       ], function requireUtils(
    urlBuilder,
) {
    'use strict';
    const cardIconBaseUrl = 'https://assets.publicsquare.com/sc/web/assets/images/cards'

    return {
        generateIdempotencyKey: function generateIdempotencyKey() {
            const timestamp = Date.now().toString();
            const random = Math.random().toString(36).substr(2, 9);
            return timestamp + random;
        },
        createCartUrl: function createCartUrl({quote, customerModel}) {
            if (customerModel.isLoggedIn()) {
                return urlBuilder.createUrl('/carts/mine/payment-information', {quoteId: quote.getQuoteId()});
            } else {
                return urlBuilder.createUrl('/guest-carts/:quoteId/payment-information', {quoteId: quote.getQuoteId()});
            }
        },
        shouldAddQuoteAddress: function hasOnlyVirtualItems(quote) {
            return quote.getItems().every(_ => _.product_type === 'virtual');
        },
        cardIconSrc: function cardIconSrc(cardType) {
            return `${cardIconBaseUrl}/${cardType}.svg`;
        },


    };
});