<?php
/**
 * ReturnRequest
 *
 * @category  Credova
 * @package   Credova_Payments
 * @author    Credova <info@credova.com>
 * @copyright 2024 Credova
 * @license   http://opensource.org/licenses/osl-3.0.php (OSL 3.0)
 * @link      https://credova.com/
 */

namespace Credova\Payments\Model\Api;

use Credova\Payments\Api\RefundsInterface;
use Credova\Payments\Api\Data;
use Credova\Payments\Helper\Config;

class Refunds implements RefundsInterface
{
    /**
     * @var \Magento\Framework\UrlInterface
     */
    protected $urlBuilder;

    /**
     * @var \Credova\Payments\Api\Authenticated\RefundsFactory
     */
    private $returnRequestFactory;

    /**
     * @var Config
     */
    private $configHelper;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    private $checkoutSession;

    public function __construct(
        \Credova\Payments\Api\Authenticated\RefundsFactory $returnRequestFactory,
        Config $configHelper,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Framework\UrlInterface $urlBuilder
    ) {
        $this->returnRequestFactory = $returnRequestFactory;
        $this->configHelper              = $configHelper;
        $this->checkoutSession           = $checkoutSession;
        $this->urlBuilder                = $urlBuilder;
    }//end __construct()

    /**
     * Returns an application in Financial and returns the public id
     *
     * @param  string $transactionId
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function refundOrder($transactionId)
    {
        // $data['payment_id'] = $transactionId;
        // $data['amount'] = 100;
        // $data['currency'] = 'USD';
        // $data['reason'] = '';
        // /*
        //     @var \Credova\Payments\Api\Authenticated\Refunds $request
        // */
        // $request  = $this->returnRequestFactory->create(['refund' => $data]);
        // $response = $request->getResponseData();
        // return $response['id'];
    }//end returnRequest()
}//end class
