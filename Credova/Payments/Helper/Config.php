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
    const CREDOVA_ACTIVE_CONFIG_PATH                   = 'payment/credovafinancial/active';
    const CREDOVA_TITLE_CONFIG_PATH                    = 'payment/credovafinancial/title';
    const CREDOVA_API_USERNAME_CONFIG_PATH             = 'payment/credovafinancial/credova_api_username';
    const CREDOVA_API_PASSWORD_CONFIG_PATH             = 'payment/credovafinancial/credova_api_password';
    const CREDOVA_STORE_CODE_CONFIG_PATH               = 'payment/credovafinancial/credova_api_username';
    const CREDOVA_MINIMUM_AMOUNT_CONFIG_PATH           = 'payment/credovafinancial/min_amount';
    const CREDOVA_MAXIMUM_AMOUNT_CONFIG_PATH           = 'payment/credovafinancial/max_amount';
    const CREDOVA_ALLOW_SPECIFIC_COUNTRIES_CONFIG_PATH = 'payment/credovafinancial/allowspecific';
    const CREDOVA_SPECIFIC_COUNTRY_CONFIG_PATH         = 'payment/credovafinancial/specificcountry';
    const CREDOVA_LOGGING_ENABLED_CONFIG_PATH          = 'payment/credovafinancial/debug';
    const CREDOVA_SORT_ORDER_CONFIG_PATH               = 'payment/credovafinancial/sort_order';
    const CREDOVA_ENVIRONMENT_CONFIG_PATH              = 'payment/credovafinancial/environment';
    const CREDOVA_PRODUCT_DISPLAY_CONFIG_PATH          = 'payment/credovafinancial/display_product_page';
    const CREDOVA_CATEGORY_DISPLAY_CONFIG_PATH         = 'payment/credovafinancial/display_category_page';
    const CREDOVA_CHECKOUT_DISPLAY_CONFIG_PATH         = 'payment/credovafinancial/display_checkout_page';
    const CREDOVA_CHECKOUT_DISPLAY_TEXT                = 'payment/credovafinancial/display_payment_text';
    const CREDOVA_ENABLE_WHITE_LOGO                    = 'payment/credovafinancial/enable_white_logo';
    const CREDOVA_HIDE_BRAND                           = 'payment/credovafinancial/hide_brand';
    const CREDOVA_POPUP_TYPE                           = 'payment/credovafinancial/popup_type';
    const CREDOVA_CHECKOUT_FLOW                        = 'payment/credovafinancial/checkout_flow_type';

    const CREDOVA_ASLOWAS_CART_PAGE                    = 'payment/credovafinancial/display_aslowas_cart_page';
    const CREDOVA_ASLOWAS_MINI_CART_PAGE               = 'payment/credovafinancial/display_aslowas_minicart_page';
    const CUSTOMER_ASSIGN_ESCAPE               = 'payment/credovafinancial/customer_assign_escape';

    /**
     * Get credova payment method active
     *
     * @param  string $scopeType
     * @param  null   $scopeCode
     * @return boolean
     */
    public function getCustomerAssignEscape(
        $scopeType = \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
        $scopeCode = null
    ): bool {
        return (bool) $this->scopeConfig
            ->getValue(self::CUSTOMER_ASSIGN_ESCAPE, $scopeType, $scopeCode);
    } //end getCredovaActive()

    /**
     * Get credova payment method active
     *
     * @param  string $scopeType
     * @param  null   $scopeCode
     * @return boolean
     */
    public function getCredovaActive(
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
     * Get credova payment method API username
     *
     * @param  string $scopeType
     * @param  null   $scopeCode
     * @return string
     */
    public function getApiUsername(
        $scopeType = \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
        $scopeCode = null
    ): string {
        return (string) $this->scopeConfig
            ->getValue(self::CREDOVA_API_USERNAME_CONFIG_PATH, $scopeType, $scopeCode);
    } //end getApiUsername()

    /**
     * Get credova payment method API password
     *
     * @param  string $scopeType
     * @param  null   $scopeCode
     * @return string
     */
    public function getApiPassword(
        $scopeType = \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
        $scopeCode = null
    ): string {
        return (string) $this->scopeConfig
            ->getValue(self::CREDOVA_API_PASSWORD_CONFIG_PATH, $scopeType, $scopeCode);
    } //end getApiPassword()

    /**
     * Get credova payment method store code
     *
     * @param  string $scopeType
     * @param  null   $scopeCode
     * @return string
     */
    public function getCredovaStoreCode(
        $scopeType = \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
        $scopeCode = null
    ): string {
        return (string) $this->scopeConfig
            ->getValue(self::CREDOVA_STORE_CODE_CONFIG_PATH, $scopeType, $scopeCode);
    } //end getCredovaStoreCode()

    /**
     * Get credova payment method minimum order amount
     *
     * @param  string $scopeType
     * @param  null   $scopeCode
     * @return float
     */
    public function getCredovaMinimumAmount(
        $scopeType = \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
        $scopeCode = null
    ): float {
        return (float) $this->scopeConfig
            ->getValue(self::CREDOVA_MINIMUM_AMOUNT_CONFIG_PATH, $scopeType, $scopeCode);
    } //end getCredovaMinimumAmount()

    /**
     * Get credova payment method maximum order amount
     *
     * @param  string $scopeType
     * @param  null   $scopeCode
     * @return float
     */
    public function getCredovaMaximumAmount(
        $scopeType = \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
        $scopeCode = null
    ): float {
        return (float) $this->scopeConfig
            ->getValue(self::CREDOVA_MAXIMUM_AMOUNT_CONFIG_PATH, $scopeType, $scopeCode);
    } //end getCredovaMaximumAmount()

    /**
     * Get credova payment method maximum order amount
     *
     * @param  string $scopeType
     * @param  null   $scopeCode
     * @return string
     */
    public function getCheckoutFlowType(
        $scopeType = \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
        $scopeCode = null
    ): string {
        return  $this->scopeConfig
            ->getValue(self::CREDOVA_CHECKOUT_FLOW, $scopeType, $scopeCode);
    } //end getCheckoutFlowType()

    /**
     * Get credova payment method limited to specific countries
     *
     * @param  string $scopeType
     * @param  null   $scopeCode
     * @return boolean
     */
    public function getCredovaAllowSpecificCountries(
        $scopeType = \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
        $scopeCode = null
    ): bool {
        return (bool) $this->scopeConfig
            ->getValue(self::CREDOVA_ALLOW_SPECIFIC_COUNTRIES_CONFIG_PATH, $scopeType, $scopeCode);
    } //end getCredovaAllowSpecificCountries()

    /**
     * Get credova payment method specific allowed countries
     *
     * @param  string $scopeType
     * @param  null   $scopeCode
     * @return array
     */
    public function getCredovaSpecificCountries(
        $scopeType = \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
        $scopeCode = null
    ): array {
        return explode(
            ',',
            $this->scopeConfig
                ->getValue(self::CREDOVA_SPECIFIC_COUNTRY_CONFIG_PATH, $scopeType, $scopeCode)
        );
    } //end getCredovaSpecificCountries()

    /**
     * Get credova payment method debug logging enabled
     *
     * @param  string $scopeType
     * @param  null   $scopeCode
     * @return boolean
     */
    public function getCredovaLoggingEnabled(
        $scopeType = \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
        $scopeCode = null
    ): bool {
        return (bool) $this->scopeConfig
            ->getValue(self::CREDOVA_LOGGING_ENABLED_CONFIG_PATH, $scopeType, $scopeCode);
    } //end getCredovaLoggingEnabled()

    /**
     * Get credova payment method sort order
     *
     * @param  string $scopeType
     * @param  null   $scopeCode
     * @return integer
     */
    public function getCredovaSortOrder(
        $scopeType = \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
        $scopeCode = null
    ): int {
        return (int) $this->scopeConfig
            ->getValue(self::CREDOVA_SORT_ORDER_CONFIG_PATH, $scopeType, $scopeCode);
    } //end getCredovaSortOrder()

    /**
     * Get credova payment method sort order
     *
     * @param  string $scopeType
     * @param  null   $scopeCode
     * @return string
     */
    public function getCredovaEnvironment(
        $scopeType = \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
        $scopeCode = null
    ): string {
        return $this->scopeConfig
            ->getValue(self::CREDOVA_ENVIRONMENT_CONFIG_PATH, $scopeType, $scopeCode);
    } //end getCredovaEnvironment()

    /**
     * Get credova payment method sort order
     *
     * @param  string $scopeType
     * @param  null   $scopeCode
     * @return string
     */
    public function getCredovaProductPageDisplay(
        $scopeType = \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
        $scopeCode = null
    ): string {
        return $this->scopeConfig
            ->getValue(self::CREDOVA_PRODUCT_DISPLAY_CONFIG_PATH, $scopeType, $scopeCode);
    } //end getCredovaProductPageDisplay()

    /**
     * Get credova payment method sort order
     *
     * @param  string $scopeType
     * @param  null   $scopeCode
     * @return string
     */
    public function getCredovaCategoryPageDisplay(
        $scopeType = \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
        $scopeCode = null
    ): string {
        return $this->scopeConfig
            ->getValue(self::CREDOVA_CATEGORY_DISPLAY_CONFIG_PATH, $scopeType, $scopeCode);
    } //end getCredovaCategoryPageDisplay()

    /**
     * Get credova payment method sort order
     *
     * @param  string $scopeType
     * @param  null   $scopeCode
     * @return string
     */
    public function getCredovaCheckoutPageDisplay(
        $scopeType = \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
        $scopeCode = null
    ): string {
        return $this->scopeConfig
            ->getValue(self::CREDOVA_CHECKOUT_DISPLAY_CONFIG_PATH, $scopeType, $scopeCode);
    } //end getCredovaCheckoutPageDisplay()

    /**
     * Get credova payment method sort order
     *
     * @param  string $scopeType
     * @param  null   $scopeCode
     * @return string
     */
    public function getCredovaShowText(
        $scopeType = \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
        $scopeCode = null
    ): string {
        return $this->scopeConfig
            ->getValue(self::CREDOVA_CHECKOUT_DISPLAY_TEXT, $scopeType, $scopeCode);
    } //end getCredovaShowText()

    /**
     * Get credova White logo enable
     *
     * @param  string $scopeType
     * @param  null   $scopeCode
     * @return string
     */
    public function getCredovaWhiteLogoEnable(
        $scopeType = \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
        $scopeCode = null
    ): string {
        return $this->scopeConfig
            ->getValue(self::CREDOVA_ENABLE_WHITE_LOGO, $scopeType, $scopeCode);
    } //end getCredovaWhiteLogoEnable()

    /**
     * Get credova Hide Brand logo
     *
     * @param  string $scopeType
     * @param  null   $scopeCode
     * @return string
     */
    public function getCredovaHideBrand(
        $scopeType = \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
        $scopeCode = null
    ): string {
        return $this->scopeConfig
            ->getValue(self::CREDOVA_HIDE_BRAND, $scopeType, $scopeCode);
    } //end getCredovaHideBrand()

    /**
     * Get credova POPUP TYPE
     *
     * @param  string $scopeType
     * @param  null   $scopeCode
     * @return string
     */
    public function getCredovaPopupType(
        $scopeType = \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
        $scopeCode = null
    ): string {
        return $this->scopeConfig
            ->getValue(self::CREDOVA_POPUP_TYPE, $scopeType, $scopeCode);
    } //end getCredovaHideBrand()

    public function getCredovaAslowasOnCart(
        $scopeType = \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
        $scopeCode = null
    ): string {
        return $this->scopeConfig
            ->getValue(self::CREDOVA_ASLOWAS_CART_PAGE, $scopeType, $scopeCode);
    } //end getCredovaAslowasOnCart()


    public function getCredovaAslowasOnMiniCart(
        $scopeType = \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
        $scopeCode = null
    ): string {
        return $this->scopeConfig
            ->getValue(self::CREDOVA_ASLOWAS_MINI_CART_PAGE, $scopeType, $scopeCode);
    } //end getCredovaAslowasOnCart()

    public function getUrii(): string
    {
        if ($this->getCredovaEnvironment() == 1) {
            $host = rtrim('https://api-staging.credova.com/', '/');
        } else {
            $host = rtrim('https://api.credova.com/', '/');
        }
        return $host;
    } //end getUrii()

    public function getApplicationData($publicId)
    {
        $authentication_token = $this->getAuthenticationToken();
        $urll2                = $this->getUrii();
        $request_url          = $urll2 . '/v2/applications/' . $publicId . '/status';
        $headerss = [
            "Content-Type: application/json",
            "Authorization: Bearer " . $authentication_token,
        ];
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $request_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headerss);
        $response1 = curl_exec($ch);
        curl_close($ch);
        return json_decode($response1, true);
    }//return application details


    public function sendOrdersToCredova($publicId, $fields_data)
    {
        $authentication_token = $this->getAuthenticationToken();
        $urll2                = $this->getUrii();
        $request_url          = $urll2 . '/v2/applications/' . $publicId . '/orders';
        $headerss = [
            "Content-Type: application/json",
            "Authorization: Bearer " . $authentication_token,
        ];
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $request_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headerss);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, []);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields_data));
        $response = curl_exec($ch);
        return json_decode($response, true);
    }

    public function getCredovaSimpleStatus($credovaPublicId)
    {
        $url        = $this->getUrii();
        $request_url          = $url.'/v2/applications/'.$credovaPublicId.'/simplifiedstatus';
        $curl = curl_init();
        curl_setopt_array(
            $curl,
            [
            CURLOPT_URL => $request_url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            ]
        );
        $response = curl_exec($curl);
        curl_close($curl);
        return json_decode($response, true);
    }

    public function getAuthenticationToken()
    {
        $token_url = $this->getUrii()."/v2/token";
        $headers = ["Content-Type: application/x-www-form-urlencoded",];
        $fields = [
                "username" => $this->getApiUsername(),
                "password" => $this->getApiPassword(),
            ];
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $token_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($fields));
        $response             = curl_exec($ch);
        curl_close($ch);
        $decoded              = json_decode($response, true);
        return $decoded["jwt"];
    }//return authentication token


    public function getLowestPaymentOption($total)
    {
        $url       = $this->getUrii();
        $apiusername = $this->getApiusername();
        $request_url = $url . '/v2/calculator/store/' . $apiusername . '/amount/' . $total . '/lowestPaymentOption';
        $headerss = [
            "Content-Type: application/json",
        ];
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $request_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headerss);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, []);
        $response = curl_exec($ch);
        return json_decode($response, true);
    }//get lowest amount

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


    public function checkStatusByPhone($phone)
    {
        $authentication_token = $this->getAuthenticationToken();
        $headers = [
          "Content-Type: application/json",
          "Authorization: Bearer ".$authentication_token
        ];
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://sandbox-lending-api.credova.com/v2/applications/phone/".$phone."/status");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $response = curl_exec($ch);

        $result = json_decode($response, true);
        return $result["status"];
    }
} //end class
