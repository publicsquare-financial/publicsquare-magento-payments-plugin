<?php
/**
 * Config helper
 *
 * @category  PublicSquare
 * @package   PublicSquare_Payments
 * @author    PublicSquare <info@publicsquare.com>
 * @copyright 2024 PublicSquare
 * @license   http://opensource.org/licenses/osl-3.0.php (OSL 3.0)
 * @link      https://publicsquare.com/
 */

namespace PublicSquare\Payments\Helper;

class Config extends \Magento\Framework\App\Helper\AbstractHelper
{
    const CODE = 'publicsquare_payments';
    const VAULT_CODE = 'publicsquare_payments_cc_vault';
    const PUBLICSQUARE_ACTIVE_CONFIG_PATH                   = 'payment/publicsquare_payments/active';
    const PUBLICSQUARE_TITLE_CONFIG_PATH                    = 'payment/publicsquare_payments/title';
    const PUBLICSQUARE_ENVIRONMENT                          = 'payment/publicsquare_payments/environment';
    const PUBLICSQUARE_API_PUBLIC_KEY                       = 'payment/publicsquare_payments/publicsquare_api_public_key';
    const PUBLICSQUARE_API_SECRET_KEY                       = 'payment/publicsquare_payments/publicsquare_api_secret_key';
    const PUBLICSQUARE_SECURITY_TYPE                        = 'payment/publicsquare_payments/security_type';
    const PUBLICSQUARE_PRE_AUTHORIZATION_TYPE               = 'payment/publicsquare_payments/pre_authorization_type';
    const PUBLICSQUARE_CVV_VERIFICATION                     = 'payment/publicsquare_payments/cvv_verification';
    const PUBLICSQUARE_THREE_D_SECURE_AUTHENTICATION        = 'payment/publicsquare_payments/three_d_secure_authentication';
    const PUBLICSQUARE_CARD_TYPES                           = 'payment/publicsquare_payments/card_types';
    const PUBLICSQUARE_PAYMENT_ACTION                       = 'payment/publicsquare_payments/payment_action';
    const PUBLICSQUARE_LOGGING_CONFIG_PATH                  = 'payment/publicsquare_payments/debug';

    /**
     * Get publicsquare payment method active
     *
     * @param  string $scopeType
     * @param  null   $scopeCode
     * @return boolean
     */
    public function getActive(
        $scopeType = \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
        $scopeCode = null
    ): bool {
        return (bool) $this->scopeConfig
            ->getValue(self::PUBLICSQUARE_ACTIVE_CONFIG_PATH, $scopeType, $scopeCode);
    } //end getPublicSquareActive()

    /**
     * Get publicsquare payment method title
     *
     * @param  string $scopeType
     * @param  null   $scopeCode
     * @return string
     */
    public function getPublicSquareTitle(
        $scopeType = \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
        $scopeCode = null
    ): string {
        return (string) $this->scopeConfig
            ->getValue(self::PUBLICSQUARE_TITLE_CONFIG_PATH, $scopeType, $scopeCode);
    } //end getPublicSquareTitle()

    /**
     * Get public api key
     *
     * @param  string $scopeType
     * @param  null   $scopeCode
     * @return string
     */
    public function getPublicAPIKey(
        $scopeType = \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
        $scopeCode = null
    ): string {
        return (string) $this->scopeConfig
            ->getValue(self::PUBLICSQUARE_API_PUBLIC_KEY, $scopeType, $scopeCode);
    } //end getPublicAPIKey()

    /**
     * Get secret api key
     *
     * @param  string $scopeType
     * @param  null   $scopeCode
     * @return string
     */
    public function getSecretAPIKey(
        $scopeType = \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
        $scopeCode = null
    ): string {
        return (string) $this->scopeConfig
            ->getValue(self::PUBLICSQUARE_API_SECRET_KEY, $scopeType, $scopeCode);
    } //end getSecretAPIKey()

    /**
     * Get publicsquare payment method sort order
     *
     * @param  string $scopeType
     * @param  null   $scopeCode
     * @return string
     */
    public function getEnvironment(
        $scopeType = \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
        $scopeCode = null
    ): string {
        return $this->scopeConfig
            ->getValue(self::PUBLICSQUARE_ENVIRONMENT, $scopeType, $scopeCode);
    } //end getPublicSquareEnvironment()

    /**
     * Get publicsquare payment method debug logging enabled
     *
     * @param  string $scopeType
     * @param  null   $scopeCode
     * @return boolean
     */
    public function getLoggingEnabled(
        $scopeType = \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
        $scopeCode = null
    ): bool {
        return (bool) $this->scopeConfig
            ->getValue(self::PUBLICSQUARE_LOGGING_CONFIG_PATH, $scopeType, $scopeCode);
    } //end getPublicSquareLoggingEnabled()

    /**
     * Get publicsquare payment capture action
     *
     * @param  string $scopeType
     * @param  null   $scopeCode
     * @return string
     */
    public function getPaymentAction(
        $scopeType = \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
        $scopeCode = null
    ): string {
        return (string) $this->scopeConfig
            ->getValue(self::PUBLICSQUARE_PAYMENT_ACTION, $scopeType, $scopeCode);
    } //end getPaymentAction()

    public function getUrii(): string
    {
        return rtrim('https://api.publicsquare.com/', '/');
    } //end getUrii()

    /**
     * Get publicsquare payment allowed currencies
     *
     * @param  string $scopeType
     * @param  null   $scopeCode
     * @return string
     */
    public function getAllowedCurrencies(
        $scopeType = \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
        $scopeCode = null
    ): array {
        return ['USD'];
    } //end getCaptureAction()

    public function getPreAuthorizationType(
        $scopeType = \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
        $scopeCode = null
    ): string {
        return $this->scopeConfig
            ->getValue(self::PUBLICSQUARE_PRE_AUTHORIZATION_TYPE, $scopeType, $scopeCode);
    }
} //end class
