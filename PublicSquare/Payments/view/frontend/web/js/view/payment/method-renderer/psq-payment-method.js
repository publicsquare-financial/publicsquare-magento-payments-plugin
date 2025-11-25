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
        'PublicSquare_Payments/js/card-form-factory',
    ],
    function requirePsqPaymentMethod(
        $,
        ko,
        CreditCardForm,
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
        cardFormFactory,
    ) {
        'use strict';

        console.log('psq-config: %o', PSQConfig)
        const config = new PSQConfig({rawConfig: window.checkoutConfig});

        let psqSdk = undefined;


        return CreditCardForm.extend(
            {
                defaults: {
                    template: 'PublicSquare_Payments/payment/psq-payment-method-template',
                    processing: ko.observable(true),

                    additional_data: {},
                    submitting: false,
                    idempotencyKey: undefined,
                    showSaveCard: ko.observable(true),
                    shouldSaveCard: ko.observable(false),
                    cardholderName: ko.observable(''),
                    getCardFormLayout: () => config.cardFormLayout(),
                    cardForm: undefined,
                    showCardholderInput: () => false,
                    isVaultEnabled: function isVaultEnabled() {
                        return config.isVaultEnabled(this.vaultEnabler());
                    },
                    vaultEnabler: ko.observable(),


                },
                // internal
                initialize: function initialize() {
                    this._super();
                    try {
                        console.log('psq: Initializing card form');
                        this.processing.subscribe((value) => {
                            if (value === true) {
                                fullScreenLoader.startLoader();
                            } else {
                                fullScreenLoader.stopLoader();
                            }
                        });

                        const vaultEnabler = new VaultEnabler();
                        vaultEnabler.setPaymentCode(config.vaultCode());
                        vaultEnabler.isActivePaymentTokenEnabler(false);
                        this.vaultEnabler(vaultEnabler);
                        const billingAddress = quote.billingAddress();
                        this.cardholderName(`${billingAddress.firstname} ${billingAddress.lastname}`);

                        this.cardForm = cardFormFactory({type: this.getCardFormLayout()});
                        console.log('psq: Card form initialized.');

                        return this;
                    } catch (error) {
                        console.error('psq: Failed to initialize card form!', error);
                    }

                },
                // internal
                onContainerRendered: async function onContainerRendered() {
                    try {
                        console.log('psq: Container rendered');
                        this.processing(true);
                        this.cardForm.bind(
                            {
                                psqPublicKey: config.psqPublicKey,
                                cardholderNameSelectorOrSupplier: () => this.cardholderName(),
                            },
                        );
                        console.log('psq: Card form bound.');
                    } catch (err) {
                        console.error('psq: Failed to bind psq elements sdk.', err);
                    } finally {
                        this.processing(false);
                    }
                },
                // internal
                createCard: async function createCard() {
                    try {
                        console.log('psq: Creating new card...');
                        if (!this.cardholderName()) {
                            const billingAddr = quote.billingAddress();
                            this.cardholderName(`${billingAddr.firstname} ${billingAddr.lastname}`);
                        }
                        return await this.form.createCard(this.cardholderName());
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
                        console.log('psq: Begin placing order...');
                        this.processing(true);
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
                        if (!card) {
                            return;
                        }
                        this.card = card;
                        console.log('psq: Successfully created card. Submitting order');


                        if (!this.idempotencyKey) {
                            this.idempotencyKey = utils.generateIdempotencyKey();
                        }
                        const placeOrderReqBody = {
                            paymentMethod: this.getData(),
                        };
                        this.vaultEnabler().visitAdditionalData(placeOrderReqBody.paymentMethod.additional_data);
                        if (this.shouldSaveCard() === true) {
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
                        this.card = undefined;

                        const successUrl = config.submitOrderSuccessUrl();
                        console.log('psq: Order %j submit success redirecting to %s', orderPlaced, successUrl);

                        $.mage.redirect(successUrl);

                    } catch (err) {
                        console.error('psq: Error when placing order', err);
                        let message = err.responseJSON?.message || err.message;
                        messageList.addErrorMessage({message: $t(message)});
                    } finally {
                        this.submitting = false;
                        this.processing(false);
                    }
                },

                /**
                 * @override
                 * @returns {*}
                 */
                getCode: function getCode() {
                    return config.getBasePath();
                },
                /**
                 * @override
                 * @returns {*}
                 */
                getData: function getData() {
                    return {
                        method: config.getBasePath(),
                        additional_data: {
                            ...this.additional_data,

                            cardId: this.card?.cardId,
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
                    };
                },

                /**
                 * @override
                 * @returns {*}
                 */
                getCcAvailableTypes: function getCcAvailableTypes() {
                    return config.cardTypes().map(type => {
                        return {
                            type,
                            getTitle: () => type,
                            getIconSrc: () => `${config.cardImagesBasePath()}/${type}.svg`,
                            getAltText: () => 'credit card type',
                            getClasses: () => `psq-form__cc-type--${type}`,
                        }
                    });
                },
                /**
                 * @override
                 * @returns {*}
                 */
                getIcons: function getIcons(type) {
                    if(type) {
                        return this.getCcAvailableTypes().filter((_) => _.type === type)[0];
                    }
                    return this.getCcAvailableTypes().filter((_) => (_.type !== 'diner' && _.type !== 'jcb'));
                },
                /**
                 * @override
                 * @returns {*}
                 */
                hasVerification: function hasVerification() {return true;},
                /**
                 * @override
                 * @returns {*}
                 */
                getCvvImageUrl: function getCvvImageUrl() {return window.checkoutConfig.payment.ccform.cvvImageUrl[this.getCode()];},
                /**
                 * @override
                 * @returns {*}
                 */
                getCvvImageHtml: function getCvvImageUrl() {
                    return '<img src="' + this.getCvvImageUrl() +
                           '" alt="' + $t('Card Verification Number Visual Reference') +
                           '" title="' + $t('Card Verification Number Visual Reference') +
                           '" />';
                },

                /**
                 * @override
                 * @returns {*}
                 */
                isShowLegend: function isShowLegend() {return true;},
                /**
                 * @override
                 * @returns {*}
                 */
                getInfo: function getInfo() {return {}},


            },
        );

    },
);