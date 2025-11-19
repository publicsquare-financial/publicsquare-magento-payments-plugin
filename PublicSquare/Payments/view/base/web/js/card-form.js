define([
           'jquery',
           'publicsquarejs',
           'Magento_Ui/js/modal/alert',
       ], function psqAddCCInit(
    $,
    publicsquare,
    modal) {
    let psq = null;
    let cardNum = null;
    let cardCVC = null;
    let cardExp = null;
    let $cardholderName = () => {};


    function bindCardholderName(selectorOrSupplier) {
        if(typeof  selectorOrSupplier === 'function') {
            $cardholderName = selectorOrSupplier;
        } else {
            $cardholderName = () => $(selectorOrSupplier || '#psq-cardholder').val();
        }
    }

    function validateSplit() {
        if (!cardNum || !cardExp || !cardCVC) {
            console.warn('Form not initialized');
            throw Error('Credit card form not initialized!');
        }

        if (!cardNum.metadata.valid || !cardExp.metadata.valid || !cardCVC.metadata.valid) {
            console.warn('Invalid user input cardNumber:%j cardExpiration:%j cardCvc:%j', cardNum.metadata, cardExp.metadata, cardCVC.metadata);
            modal({
                      title: 'Error',
                      content: 'The card is invalid. Please check the card details and try again.',
                      clickableOverlay: true,
                      actions: {
                          always: function () { /*noop*/ },
                      },
                  });
            return false;
        }
        return true;
    }

    const cardFormSplit = {
        bind: async (
            {psqPublicKey, cardNumberSelector, cardExpirationSelector, cardVerificationSelector, cardholderNameSelectorOrSupplier},
        ) => {
            if (psq === null) {
                psq = await publicsquare.init(psqPublicKey);
            }
            cardNum = psq.createCardNumberElement();
            cardNum.mount(cardNumberSelector || "#psq-card-num");

            cardExp = psq.createCardExpirationDateElement();
            cardExp.mount(cardExpirationSelector || "#psq-exp");

            cardCVC = psq.createCardVerificationCodeElement();
            cardCVC.mount(cardVerificationSelector || "#psq-cvc");

            bindCardholderName(cardholderNameSelectorOrSupplier);
        },
        createCard: async () => {
            try {
                if (validateSplit()) {
                    return await psq.cards.create(
                        {
                            cardholder_name: $cardholderName(),
                            card: {
                                number: cardNum,
                                expirationMonth: cardExp.month(),
                                expirationYear: cardExp.year(),
                                cvc: cardCVC,
                            },
                        },
                    );
                }
            } catch (err) {
                console.error('Failed creating card!', err);
                modal({
                          title: 'Error',
                          content: 'Failed to create card!',
                          clickableOverlay: true,
                          actions: {
                              always: function () { /*noop*/ },
                          },
                      });
            }
        },

    };

    let card = null;

    function validateSingle() {
        if (!card) {
            console.warn('Form not initialized');
            throw Error('Credit card form not initialized!');
        }
        if (!card.metadata.valid) {
            console.warn('Invalid card! %j', card.metadata);

            modal({
                      title: 'Error',
                      content: 'The card is invalid. Please check the card details and try again.',
                      clickableOverlay: true,
                      actions: {
                          always: function () { /*noop*/ },
                      },
                  });
            return false;
        }
        return true;
    }

    const cardFormSingle = {
        bind: async (
            {psqPublicKey, cardSelector, cardholderNameSelectorOrSupplier},
        ) => {
            if (psq === null) {
                psq = await publicsquare.init(psqPublicKey);
            }
            card = psq.createCardElement();
            card.mount(cardSelector || "#psq-card");

            bindCardholderName(cardholderNameSelectorOrSupplier);
        },
        createCard: async () => {
            try {
                if (validateSingle()) {
                    return await psq.cards.create(
                        {
                            cardholder_name: $cardholderName(),
                            card,
                        },
                    );
                }
            } catch (err) {
                console.error('Failed creating card!', err);
                modal({
                          title: 'Error',
                          content: 'Failed to create card!',
                          clickableOverlay: true,
                          actions: {
                              always: function () { /*noop*/ },
                          },
                      });
            }
        },

    };


    return function createCardForm({type}) {
        switch (type) {
            case 'single':
                return cardFormSingle;
            case 'split-a':
                return cardFormSplit;
            default:
                console.warn('Unknown form type %s', type);
                throw Error(`Form type '${type}' is not valid!`);
        }
    };
});