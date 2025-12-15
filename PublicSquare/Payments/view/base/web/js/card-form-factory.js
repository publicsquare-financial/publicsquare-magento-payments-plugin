define(
    [
        'jquery',
        'publicsquarejs',
        'Magento_Ui/js/model/messageList',
        'mage/translate',
        'ko',
    ],
    function requireCardForm(
        $,
        publicsquare,
        messageList,
        $t,
        ko,
    ) {
        let psq = null;
        let cardNum = null;
        let cardCVC = null;
        let cardExp = null;
        let $cardholderName = () => {};


        function bindCardholderName(selectorOrSupplier) {
            if (typeof selectorOrSupplier === 'function') {
                $cardholderName = selectorOrSupplier;
            } else {
                $cardholderName = () => $(selectorOrSupplier || '#psq-cardholder').val();
            }
        }

        function validateSplit() {
            if (!cardNum || !cardExp || !cardCVC) {
                console.warn('Form not initialized');
                throw new Error('Credit card form not initialized!');
            }

            if (!cardExp.metadata.valid || !cardCVC.metadata.valid) {
                console.warn('Invalid user input cardNumber:%j cardExpiration:%j cardCvc:%j', cardNum.metadata, cardExp.metadata, cardCVC.metadata);
                throw new Error('The card is invalid. Please check the card details and try again.');
            }
            if (cardNum.metadata.errors?.length > 0) {
                console.warn('psq: Card Number Errors %j', cardNum.metadata.errors);
                throw new Error('The card is invalid. Please check the card number and try again.');
            }
            if (cardExp.metadata.errors?.length > 0) {
                console.warn('psq: Card Expiration Errors %j', cardExp.metadata.errors);
                throw new Error('The card is invalid. Please check the card expiration and try again.');
            }
            if (cardCVC.metadata.errors?.length > 0) {
                console.warn('psq: Card Verification Errors %j', cardCVC.metadata.errors);
                throw new Error('The card is invalid. Please check the card verification code and try again.');
            }
            return true;
        }

        const cardFormSplit = {
            bound: ko.observable(false),
            unbind: function unbind() {
                cardNum?.unmount();
                cardExp?.unmount();
                cardCVC?.unmount();

                cardNum = null;
                cardExp = null;
                cardCVC = null;

                this.bound(false);
            },
            bind: async function bind(
                {psqPublicKey, cardNumberSelector, cardExpirationSelector, cardVerificationSelector, cardholderNameSelectorOrSupplier, cardInputCustomization},
            ) {
                if (psq === null) {
                    psq = await publicsquare.init(psqPublicKey);
                }
                if (this.bound()) {
                    console.trace('psq: Form already bound!');
                    return;
                }
                cardNum = psq.createCardNumberElement(cardInputCustomization);
                cardNum.mount(cardNumberSelector || "#psq-card-num");

                cardExp = psq.createCardExpirationDateElement(cardInputCustomization);
                cardExp.mount(cardExpirationSelector || "#psq-exp");

                cardCVC = psq.createCardVerificationCodeElement(cardInputCustomization);
                cardCVC.mount(cardVerificationSelector || "#psq-cvc");

                bindCardholderName(cardholderNameSelectorOrSupplier);
                this.bound(true);
            },
            createCard: async function createCard(attempt = 1)  {
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
                    if (attempt <= 3 && err.data?.includes('Timeout')) {
                        await this.createCard(attempt + 1);
                    } else {
                        messageList.addErrorMessage({message: $t(err.message || 'Failed to create card!')});
                    }
                }
            },

        };

        let card = null;

        function validateSingle() {
            if (!card) {
                console.warn('Form not initialized');
                throw new Error('Credit card form not initialized!');
            }
            if (!card.metadata.valid) {
                console.warn('Invalid card! %j', card.metadata);
                throw new Error('The card is invalid. Please check the card details and try again.');
            }
            if (card.metadata.errors?.length > 0) {
                console.warn('psq: Card Has Errors %j', card.metadata.errors);
                throw new Error('The card is invalid. Please check the card details and try again.');
            }
            return true;
        }

        const cardFormSingle = {
            bound: ko.observable(false),
            unbind: function unbind()  {
                card?.unmount();
                card = null;
                this.bound(false);
            },
            bind: async function bind(
                {psqPublicKey, cardSelector, cardholderNameSelectorOrSupplier, cardInputCustomization},
            ) {
                if (psq === null) {
                    psq = await publicsquare.init(psqPublicKey);
                }
                if (this.bound()) {
                    console.trace('psq: Form already bound!');
                    return;
                }
                card = psq.createCardElement(cardInputCustomization);
                card.mount(cardSelector || "#psq-card");

                bindCardholderName(cardholderNameSelectorOrSupplier);
                this.bound(true);
            },
            createCard: async function createCard(attempt = 1)  {
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
                    console.error('Failed creating card! %s', err.data, err);
                    if (attempt <= 3 && err.data?.includes('Timeout')) {
                        await this.createCard(attempt + 1);
                    } else {
                        messageList.addErrorMessage({message: $t(err.message || 'Failed to create card!')});
                    }
                }
            },

        };


        return function cardFormFactory({type}) {
            switch (type) {
                case 'single':
                    return cardFormSingle;
                case 'split-a':
                    return cardFormSplit;
                default:
                    console.warn('Unknown form type %s', type);
                    throw new Error(`Form type '${type}' is not valid!`);
            }
        };
    });