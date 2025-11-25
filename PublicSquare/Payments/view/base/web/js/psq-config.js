define([], function requirePsqConfig() {
    class PSQConfig {
        #rawConfig;
        #basePath;

        constructor({rawConfig, basePath}) {
            this.#rawConfig = rawConfig;
            this.#basePath = basePath || 'publicsquare_payments';
        }
        getBasePath() {
            return this.#basePath;
        }

        paymentConfig() {
            return this.#rawConfig['payment'];
        }
        psqPaymentConfig() {
            return this.paymentConfig()[this.#basePath];
        }

        vaultCode() {
            return this.psqPaymentConfig().ccVaultCode;
        }

        isVaultEnabled(vaultEnabler) {
            return vaultEnabler.isVaultEnabled() && (
                this.#rawConfig.vault.publicsquare_payments_cc_vault.is_enabled === true
            );
        }

        publicKey() {
            return this.psqPaymentConfig().pk;
        }

        cardFormLayout() {
            return this.psqPaymentConfig().cardFormLayout;
        }

        cardTypes() {
            return this.psqPaymentConfig().cardTypes;
        }
        cardImagesBasePath() {
            return this.psqPaymentConfig().cardImagesBasePath;
        }

        title() {
            return this.psqPaymentConfig().title;
        }

        submitOrderSuccessUrl() {
            return `${this.psqPaymentConfig().successUrl}?${this.#rawConfig.isCustomerLoggedIn ? "refercust" : "refergues"}=${this.#rawConfig.quoteData.entity_id}`;
        }



    }
    return PSQConfig;
});