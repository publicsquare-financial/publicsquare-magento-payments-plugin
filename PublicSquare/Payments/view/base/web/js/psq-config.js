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
            if (!json) {
                // Default to empty config instead of null to avoid
                // errors in the PublicSquare JS SDK when it expects an object.
                return {};
            }
            try {
                return JSON.parse(json);
            } catch (e) {
                // If admin configuration contains invalid JSON, fall back
                // gracefully rather than breaking checkout initialization.
                console.error('psq: Invalid cardInputCustomization JSON', e, json);
                return {};
            }
        }



    }
    return PSQConfig;
});