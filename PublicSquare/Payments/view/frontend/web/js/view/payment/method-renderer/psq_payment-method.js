define(
    [
        'jquery',
        'ko',
        'Magento_Payment/js/view/payment/cc-form',
        'mage/storage',
        'Magento_Checkout/js/model/quote',
        'Magento_Checkout/js/model/payment/additional-validators',
        'Magento_Checkout/js/model/full-screen-loader',
        'mage/translate',
        'Magento_Vault/js/view/payment/vault-enabler',
        'Magento_Ui/js/model/messageList',
        'Magento_Customer/js/model/customer',
        'Magento_Checkout/js/model/place-order',
        'Magento_Ui/js/modal/alert',


        'PublicSquare_Payments/js/utils',
        'PublicSquare_Payments/js/psq-config',
        'publicsquarejs',
    ],
    function requirePsqPaymentMethod(
        $,
        ko,
        CreditCardForm,
        urlBuilder,
        storge,
        quote,
        additionalValidators,
        fullScreenLoader,
        $t,
        VaultEnabler,
        messageList,
        customerModel,
        placeOrderService,
        modal,
        utils,
        PSQConfig,
        publicsquare,
    ) {
        'use strict';

        const config = new PSQConfig(window.checkoutConfig);

        let vaultEnabler = undefined;
        let psqSdk = undefined;



        return CreditCardForm.extend(
            {
                defaults: {
                    template: 'PublicSquare_Payments/payment/psq-payment-method',
                    processing: ko.observable(true),

                    submitting: false,
                    idempotencyKey: undefined,
                    shouldSaveCard: ko.observable(false),
                    cardholderName: ko.observable(''),
                    getCardFormLayout: config.cardFormLayout,
                },
                // internal
                initialize: function initialize() {
                    this._super();
                    this.processing.subscribe((value) => {
                        if (value === true) {
                            fullScreenLoader.startLoader();
                        } else {
                            fullScreenLoader.stopLoader();
                        }
                    });

                    vaultEnabler = new VaultEnabler();
                    vaultEnabler.setPaymentCode(config.vaultCode());
                    vaultEnabler.isActivePaymentTokenEnabler(false);
                    this.cardholderName.set(quo)

                    switch (this.getCardFormLayout()) {
                        case 'split-a':
                            this.form = {
                                inputs: {
                                    cardNumber: {selector: '#psq-input-card-num', ref: undefined},
                                    cardExp: {selector: '#psq-input-card-exp', ref: undefined},
                                    cardCvc: {selector: '#psq-input-card-cvc', ref: undefined},
                                },
                                bind: () => {
                                    this.inputs.cardNumber.ref?.unmount();
                                    this.inputs.cardNumber.ref = psqSdk.createCardNumberElement();
                                    this.inputs.cardNumber.mount(this.inputs.cardNumber.selector);

                                    this.inputs.cardExp.ref?.unmount();
                                    this.inputs.cardExp.ref = psqSdk.createCardExpirationDateElement();
                                    this.inputs.cardExp.mount(this.inputs.cardExp.selector);

                                    this.inputs.cardCvc.ref?.unmount();
                                    this.inputs.cardCvc.ref = psqSdk.createCardVerificationCodeElement();
                                    this.inputs.cardCvc.mount(this.inputs.cardCvc.selector);
                                },
                                createCard: async (cardholder_name) => {
                                    const refs = [
                                        this.inputs.cardNumber.ref,
                                        this.inputs.cardExp.ref,
                                        this.inputs.cardCvc.ref,
                                    ]
                                    if (refs.some(_ => !_)) {
                                        console.warn('psq: Form not initialized');
                                        throw Error('Credit card form not initialized!');
                                    }
                                    if (refs.some(_ => !_.metadata.valid)) {
                                        console.warn('psq: Invalid user input cardNumber:%j cardExpiration:%j cardCvc:%j', cardNum.metadata, cardExp.metadata, cardCVC.metadata);
                                        throw Error('The card is invalid. Please check the card details and try again.');
                                    }

                                    return await psqSdk.createCard(
                                        {
                                            cardholder_name,
                                            number: this.inputs.cardNumber.ref,
                                            expirationYear: this.inputs.cardExp.ref.year(),
                                            expirationMonth: this.inputs.cardExp.ref.month(),
                                            cvc: this.inputs.cardCvc.ref,
                                        },
                                    );
                                },
                            };
                            break;
                        default:
                            this.form = {
                                inputs: {
                                    card: {selector: '#psq-input-card', ref: undefined},
                                },
                                bind: () => {
                                    this.inputs.card.ref = psqSdk.createCardElement();
                                    this.inputs.card.mount(this.inputs.card.selector);
                                },
                                createCard: async (cardholder_name) => {
                                    const ref = this.inputs.card.ref;
                                    if (!ref) {
                                        console.warn('psq: Form not initialized');
                                        throw Error('Credit card form not initialized!');
                                    }
                                    if (!ref.metadata.valid) {
                                        console.warn('psq: Invalid user input cardNumber:%j cardExpiration:%j cardCvc:%j', cardNum.metadata, cardExp.metadata, cardCVC.metadata);
                                        throw Error('The card is invalid. Please check the card details and try again.');
                                    }

                                    return await psqSdk.createCard(
                                        {
                                            cardholder_name,
                                            card: this.inputs.card.ref,
                                        },
                                    )
                                },
                            };
                            break;
                    }
                    return this;
                },
                // internal
                onContainerRendered: async function onContainerRendered() {
                    this.processing.set(true);
                    if (!psqSdk) {
                        psqSdk = await publicsquare.init(config.publicKey());
                    }
                    this.form.bind();


                    this.processing.set(false);
                },
                // internal
                createCard: async function createCard() {
                    try {
                        if (!this.cardholderName.peek()) {
                            const billingAddr = quote.billingAddress();
                            this.cardholderName.set(`${billingAddr.firstname} ${billingAddr.lastname}`);
                        }
                        return await this.form.createCard(this.cardholderName.get());
                    } catch (err) {
                        console.error('psq: Failed to created card!', err);
                        messageList.addErrorMessage(
                            {message: $t(err.message || 'The card is invalid. Please check the card details and try again.')},
                        );
                    }
                },
                // public api
                placeOrder: async function placeOrder() {
                    try {
                        if (this.submitting) {
                            console.warn('psq: Submit already in progress...');
                            return;
                        }
                        this.processing.set(true);
                        // TODO: Do these validators return errors?
                        if (!this.validate() || !additionalValidators.validate()) {
                            console.warn('psq: Validation failed');
                            messageList.addErrorMessage(
                                {message: $t('Validation failed, please check order details.')},
                            );
                            return false;
                        }

                        self.submitting = true;
                        const card = await this.createCard();
                        if(!card) {
                            return;
                        }
                        console.log('psq: Successfully created card. Submitting order');


                        if(!this.idempotencyKey) {
                            this.idempotencyKey = utils.generateIdempotencyKey();
                        }
                        const placeOrderReqBody = {
                            paymentMethod: {
                                method: config.getBasePath(),
                                additional_data: {
                                    ...this.additional_data,

                                    cardId: card.cardId,
                                    idempotencyKey: this.idempotencyKey,
                                    saveCard: this.shouldSaveCard(),
                                },
                                ...(
                                    window.checkoutConfig.checkoutAgreements?.agreements ? {
                                        extension_attributes: {
                                            agreement_ids: window.checkoutConfig.checkoutAgreements.agreements.map(_ => _.agreementId),
                                        },
                                    } : undefined
                                ),
                            },
                        };
                        vaultEnabler.visitAdditionalData(placeOrderReqBody.paymentMethod.additional_data);
                        if(this.shouldSaveCard() === true) {
                           placeOrderReqBody.paymentMethod.additional_data.saveCard = true;
                        }

                        if (utils.shouldAddQuoteAddress(quote)) {
                            placeOrderReqBody.billingAddress = quote.billingAddres();
                        }
                        if (!customerModel.isLoggedIn()) {
                            placeOrderReqBody.email = quote.guestEmail;
                        }

                        const orderPlaced = await placeOrderService(
                            util.createCartUrl({quote, customerModel}),
                            placeOrderReqBody,
                            messageList,
                        );
                        // This is only cleared after a successful order placement to prevent
                        // double submits. It should not be cleared in the catch block.
                        this.idempotencyKey = undefined;

                        const successUrl = config.submitOrderSuccessUrl();
                        console.log('psq: Order %j submit success redirecting to %s', orderPlaced, successUrl);

                        $.mage.redirect(successUrl);

                    } catch (err) {
                        console.error('psq: Error when placing order', err);
                        let message = err.responseJSON?.message || err.message;
                        messageList.addErrorMessage({message: $t(message)});
                    } finally {
                        this.submitting = false;
                        this.processing.set(false);
                    }
                },

            },
        );

    },
);