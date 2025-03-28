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

class PaymentUpdate extends \PublicSquare\Payments\Api\ApiRequestAbstract
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
        $paymentId,
        $externalId
    ) {
        parent::__construct($clientFactory, $configHelper, $logger);
        $this->objectId = $paymentId;
        $this->requestData = [
            "external_id" => $externalId
        ];
        $this->logger->info("PSQ Payments updater", [
            "objectId" => $this->objectId,
            "requestData" => $this->requestData,
        ]);
    } //end __construct()

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
        return true;
    } //end validateResponse()
} //end class
