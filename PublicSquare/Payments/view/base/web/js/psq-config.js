define([], function requirePsqConfig() {
    return class PSQConfig {
        #rawConfig;
        #basePath;

        constructor({rawConfig, basePath}) {
            this.#rawConfig = rawConfig;
            this.#basePath = basePath || 'publicsquare_payments';
        }


        paymentConfig() {
            return thisthis.#rawConfig[this.#basePath];
        }

        vaultCode() {
            return this.paymentConfig().ccVaultCode;
        }

        isVaultEnabled(vaultEnabler) {
            return vaultEnabler.isVaultEnabled() && (
                // TODO: how to read this from js...
                this.paymentConfig().active === true
            );
        }

        publicKey() {
            return this.paymentConfig().pk;
        }

        cardFormLayout() {
            return this.paymentConfig().card_form_layou;
        }

        cardTypes() {
            return this.paymentConfig().card_types;
        }

        title() {
            return this.paymentConfig().title;
        }

        submitOrderSuccessUrl() {
            // TODO: wouldn't this be on the quote?
            return `${this.paymentConfig().successUrl}?${this.#rawConfig.isCustomerLoggedIn ? "refercust" : "refergues"}=${this.#rawConfig.quoteData.entity_id}`;
        }

        getBasePath() {
            return this.#basePath;
        }



    }
});