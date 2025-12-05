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
            // unwrap php "array object"
            return Object.values(this.psqPaymentConfig().ccAvailableTypes);
        }
        iconList() {
            // unwrap php "array object"
            return Object.values(this.psqPaymentConfig().ccFilteredTypes);
        }

        title() {
            return this.psqPaymentConfig().title;
        }

        submitOrderSuccessUrl() {
            return `${this.psqPaymentConfig().successUrl}?${this.#rawConfig.isCustomerLoggedIn ? "refercust" : "refergues"}=${this.#rawConfig.quoteData.entity_id}`;
        }

        cardInputCustomization() {
            const json = this.psqPaymentConfig().cardInputCustomization;
            return json && JSON.parse(json);
        }



    }
    return PSQConfig;
});