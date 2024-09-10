<?php
/**
 * Config helper
 *
 * @category  Credova
 * @package   Credova_Payments
 * @author    Credova <info@credova.com>
 * @copyright 2024 Credova
 * @license   http://opensource.org/licenses/osl-3.0.php (OSL 3.0)
 * @link      https://credova.com/
 */

namespace Credova\Payments\Helper;

class Config extends \Magento\Framework\App\Helper\AbstractHelper
{
    const CODE = 'credova_payments';
    const CREDOVA_ACTIVE_CONFIG_PATH                   = 'payment/credova_payments/active';
    const CREDOVA_TITLE_CONFIG_PATH                    = 'payment/credova_payments/title';
    const CREDOVA_ENVIRONMENT                          = 'payment/credova_payments/environment';
    const CREDOVA_API_PUBLIC_KEY                       = 'payment/credova_payments/credova_api_public_key';
    const CREDOVA_API_SECRET_KEY                       = 'payment/credova_payments/credova_api_secret_key';
    const CREDOVA_SECURITY_TYPE                        = 'payment/credova_payments/security_type';
    const CREDOVA_PRE_AUTHORIZATION_TYPE               = 'payment/credova_payments/pre_authorization_type';
    const CREDOVA_CVV_VERIFICATION                     = 'payment/credova_payments/cvv_verification';
    const CREDOVA_THREE_D_SECURE_AUTHENTICATION        = 'payment/credova_payments/three_d_secure_authentication';
    const CREDOVA_CARD_TYPES                           = 'payment/credova_payments/card_types';
    const CREDOVA_PAYMENT_ACTION                       = 'payment/credova_payments/payment_action';
    const CREDOVA_LOGGING_CONFIG_PATH                  = 'payment/credova_payments/debug';
    const CREDOVA_CC_VAULT_ACTIVE                      = 'payment/credova_payments/credova_payments_cc_vault';

    /**
     * Get credova payment method active
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
            ->getValue(self::CREDOVA_ACTIVE_CONFIG_PATH, $scopeType, $scopeCode);
    } //end getCredovaActive()

    /**
     * Get credova payment method title
     *
     * @param  string $scopeType
     * @param  null   $scopeCode
     * @return string
     */
    public function getCredovaTitle(
        $scopeType = \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
        $scopeCode = null
    ): string {
        return (string) $this->scopeConfig
            ->getValue(self::CREDOVA_TITLE_CONFIG_PATH, $scopeType, $scopeCode);
    } //end getCredovaTitle()

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
            ->getValue(self::CREDOVA_API_PUBLIC_KEY, $scopeType, $scopeCode);
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
            ->getValue(self::CREDOVA_API_SECRET_KEY, $scopeType, $scopeCode);
    } //end getSecretAPIKey()

    /**
     * Get credova payment method sort order
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
            ->getValue(self::CREDOVA_ENVIRONMENT, $scopeType, $scopeCode);
    } //end getCredovaEnvironment()

    /**
     * Get credova payment method debug logging enabled
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
            ->getValue(self::CREDOVA_LOGGING_CONFIG_PATH, $scopeType, $scopeCode);
    } //end getCredovaLoggingEnabled()

    /**
     * Get credova payment capture action
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
            ->getValue(self::CREDOVA_PAYMENT_ACTION, $scopeType, $scopeCode);
    } //end getPaymentAction()

    public function getUrii(): string
    {
        return rtrim('https://api.credova.com/', '/');
    } //end getUrii()

    /**
     * Get credova payment allowed currencies
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
            ->getValue(self::CREDOVA_PRE_AUTHORIZATION_TYPE, $scopeType, $scopeCode);
    }

    public function getCCVaultActive(
        $scopeType = \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
        $scopeCode = null
    ): bool {
        return (bool) $this->scopeConfig
            ->getValue(self::CREDOVA_CC_VAULT_ACTIVE, $scopeType, $scopeCode);
    }

    public function getReturnReasons()
    {
        $authentication_token = $this->getAuthenticationToken();
        $url = $this->getUrii();
        $request_url = $url.'/v2/returnreasons';
        $headerss = [
          "Content-Type: application/json",
          "Authorization: Bearer ".$authentication_token
        ];
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $request_url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headerss);
        $response = curl_exec($curl);
        curl_close($curl);
        return json_decode($response, true);
    }//get return reasons

    public function getRequestReturn($pubId, $returntype, $returnreason, $reason = '')
    {
        $authentication_token = $this->getAuthenticationToken();
        $urll2       = $this->getUrii();
        $request_url = $urll2 . '/v2/applications/' . $pubId . '/requestreturn';
        $headerss = [
            "Content-Type: application/json",
            "Authorization: Bearer " . $authentication_token,
        ];
        $fieldss = [
            "public_id"            => $pubId,
            "returnType"           => $returntype,
            "returnReasonPublicId" => $returnreason,
            "reason"               => $reason
        ];
        $body = json_encode($fieldss);
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $request_url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headerss);
        curl_setopt($curl, CURLOPT_HEADER, true); // we want headers
        curl_setopt($curl, CURLOPT_VERBOSE, true);
        curl_setopt($curl, CURLOPT_TIMEOUT, 30000);
        curl_setopt($curl, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_0);
        curl_setopt($curl, CURLOPT_ENCODING, ''); // we accept all supported data compress formats
        // comment line below to see whats flying on HTTP layer
        curl_setopt($curl, CURLINFO_HEADER_OUT, true);
        curl_setopt($curl, CURLOPT_POST, true);
        //curl_setopt($curl, CURLOPT_POSTFIELDS, array());
        //curl_setopt($curl, CURLOPT_POSTFIELDS, $fields);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $body);

        $response = curl_exec($curl);

        $result = ['headers' => '', 'body' => '', 'status_code' => '', 'json' => '', 'api_error' => ''];

        $header_size           = curl_getinfo($curl, CURLINFO_HEADER_SIZE);
        $result['headers']     = substr($response, 0, $header_size);
        $result['body']        = substr($response, $header_size);
        $result['status_code'] = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        if ($result['body']) {
            $result['json'] = json_decode($result['body'], true);
            // status_code != 200
            if ($result['json'] and array_key_exists("errors", $result['json'])) {
                $result['api_error'] = implode("", $result['json']['errors']);
            }
        }
        curl_close($curl);
        return $result;
    }//get return request
} //end class
