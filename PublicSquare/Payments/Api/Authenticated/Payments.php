<?php
/**
 * Application
 *
 * @category  PublicSquare
 * @package   PublicSquare_Payments
 * @author    PublicSquare <info@publicsquare.com>
 * @copyright 2024 PublicSquare
 * @license   http://opensource.org/licenses/osl-3.0.php (OSL 3.0)
 * @link      https://publicsquare.com/
 */

namespace PublicSquare\Payments\Api\Authenticated;

class Payments extends PublicSquareAPIRequestAbstract
{
    const PATH = "payments";

    /**
     * @var array
     */
    protected $data;

    protected $clientFactory;
    protected $configHelper;
    protected $apiHelper;
    protected $logger;

    public function __construct(
        \Laminas\Http\ClientFactory $clientFactory,
        \PublicSquare\Payments\Helper\Config $configHelper,
        \PublicSquare\Payments\Logger\Logger $logger,
        int $amount,
        string $cardId,
        bool $capture,
        string $phone,
        string $email,
        \Magento\Quote\Model\Quote\Address $shippingAddress,
        \Magento\Quote\Model\Quote\Address $billingAddress
    ) {
        parent::__construct($clientFactory, $configHelper, $logger);
        $this->requestData = [
            "amount" => $amount,
            "currency" => "USD",
            // Authorize only, because the CaptureCommand will handle capturing the payment
            "capture" => $capture,
            "payment_method" => [
                "card" => $cardId,
            ],
            "customer" => [
                "external_id" => "",
                "business_name" => "",
                "first_name" => $billingAddress->getFirstname(),
                "last_name" => $billingAddress->getLastname(),
                "email" => $email,
                "phone" => $this->formatPhoneNumber($phone),
            ],
            "billing_details" => [
                "address_line_1" => $billingAddress->getStreet()[0],
                "address_line_2" => array_key_exists(
                    1,
                    $billingAddress->getStreet()
                )
                    ? $billingAddress->getStreet()[1]
                    : "",
                "city" => $billingAddress->getCity(),
                "state" => $billingAddress->getRegionId(),
                "postal_code" => $billingAddress->getPostcode(),
                "country" => $billingAddress->getCountryId(),
            ],
            "shipping_address" => [
                "address_line_1" => $shippingAddress->getStreet()[0],
                "address_line_2" => array_key_exists(
                    1,
                    $shippingAddress->getStreet()
                )
                    ? $shippingAddress->getStreet()[1]
                    : "",
                "city" => $shippingAddress->getCity(),
                "state" => $shippingAddress->getRegionId(),
                "postal_code" => $shippingAddress->getPostcode(),
                "country" => $shippingAddress->getCountryId(),
            ],
        ];
    } //end __construct()

    static function formatPhoneNumber(string $rawPhoneNumber): string {
        $phoneNumber = str_replace(" ", "-", $rawPhoneNumber);
        $phoneNumber = preg_replace("/\D+/", "", $phoneNumber);

        if (
            preg_match('/(\d{3})(\d{3})(\d{4})$/', $phoneNumber, $matches)
        ) {
            $phoneNumber =
                $matches[1] . "-" . $matches[2] . "-" . $matches[3];
        } else {
            $phoneNumber = $phoneNumber;
        }

        if (substr_count($phoneNumber, "-") == 3) {
            $phoneNumber = substr(
                $phoneNumber,
                strpos($phoneNumber, "-") + 1
            );
        }
        return $phoneNumber;
    }

    /**
     * Get request path
     *
     * @return string
     */
    protected function getPath(): string
    {
        return static::PATH;
    } //end getPath()

    /**
     * Get request method
     *
     * @return string
     */
    protected function getMethod(): string
    {
        return \Laminas\Http\Request::METHOD_POST;
    } //end getMethod()

    protected function validateResponse(mixed $data): bool
    {
        if (in_array($data["status"], ["succeeded", "requires_capture"])) {
            $this->logger->info("PSQ Payment succeeded", [
                "response" => $this->getSanitizedResponseData(),
            ]);
            return true;
        } elseif ($data["status"] === "rejected") {
            $this->logger->error("PSQ Payment rejected", [
                "response" => $this->getSanitizedResponseData(),
            ]);
            throw new \Exception(
                __(
                    "The payment could not be completed. Please verify your details and try again."
                )
            );
        } elseif ($data["status"] === "declined") {
            $this->logger->error("PSQ Payment declined", [
                "response" => $this->getSanitizedResponseData(),
            ]);
            throw new \Exception(
                __(
                    "The payment could not be processed. Reason: " .
                        $data["declined_reason"] ??
                        "declined"
                )
            );
        } else {
            $this->logger->error("PSQ Payment failed", [
                "response" => $this->getSanitizedResponseData(),
            ]);
            throw new \Exception(
                __(
                    "The payment could not be completed. Please verify your details and try again."
                )
            );
        }
    } //end validateResponse()
} //end class
