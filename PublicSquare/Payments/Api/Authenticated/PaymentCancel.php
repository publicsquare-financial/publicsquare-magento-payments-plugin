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

// use PublicSquare\Payments\Api\Data\ApplicationInfoInterface;

class PaymentCancel extends PublicSquareAPIRequestAbstract
{
    const PATH = 'payments/cancel';

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
        array $payment = []
    ) {
        parent::__construct($clientFactory, $configHelper, $logger);
        $this->requestData = $payment;
    }//end __construct()

    /**
     * Get request path
     *
     * @return string
     */
    protected function getPath(): string
    {
        return static::PATH;
    }//end getPath()

    /**
     * Get request method
     *
     * @return string
     */
    protected function getMethod(): string
    {
        return \Laminas\Http\Request::METHOD_POST;
    }//end getMethod()

    protected function validateResponse(mixed $data): bool
    {
        if ($this->getResponse()->isSuccess()) {
            $this->logger->info("PSQ Payment cancel succeeded", ["response" => $this->getSanitizedResponseData()]);
            return true;
        } else {
            $this->logger->error("PSQ Payment cancel failed", ["response" => $this->getSanitizedResponseData()]);
            throw new \Exception("The payment could not be successfully canceled. Please verify your details and try again.");
        }
    } //end validateResponse()
}//end class
