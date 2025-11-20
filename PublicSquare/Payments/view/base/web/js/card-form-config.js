define([
           'ko',

       ], function requireCardFormModel(
    ko,
    customerModel,
) {
    'use strict';
    const saveCard = ko.observable(false);

    const config = {


        isVaultEnabled: function isVaultEnabled() {
            return window.checkoutConfig.payment.publicsquare_payments.isVaultEnabled() &&
                   window.checkoutConfig.payment.isVaultEnabled();
        },
        getCardFormLayout: function getCardFormLayout() {
            return window.checkoutConfig.payment.publicsquare_payments.cardFormLayout;
        },

        showCardholderInput: ko.observable(false),
        showSaveCard: ko.observable(false),

        newCard: ko.observable(undefined),




    };
    function shouldSaveCard(value) {
        if(value && model.showSaveCard.peek()) {
            saveCard.set(value);
        }
        return saveCard.get();
    }
    config.shouldSaveCard = shouldSaveCard;


    return config;
});