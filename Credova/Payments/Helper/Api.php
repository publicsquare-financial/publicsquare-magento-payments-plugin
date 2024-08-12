<?php
/**
 * Api helper
 *
 * @category  Credova
 * @package   Credova_Payments
 * @author    Credova <info@credova.com>
 * @copyright 2024 Credova
 * @license   http://opensource.org/licenses/osl-3.0.php (OSL 3.0)
 * @link      https://credova.com/
 */

namespace Credova\Payments\Helper;

use Magento\Framework\App\Helper\Context;

class Api extends \Magento\Framework\App\Helper\AbstractHelper
{
    protected $apiToken = null;

    /**
     * @var \Credova\Payments\Api\AuthTokenRequest
     */
    protected $authTokenRequest;

    

    /**
     * Api constructor.
     *
     * @param Context                                 $context
     * @param \Credova\Payments\Api\AuthTokenRequest $authTokenRequest
     */
    public function __construct(
        Context $context,
        // End parent parameters
        \Credova\Payments\Api\AuthTokenRequest $authTokenRequest
    ) {
        parent::__construct($context);
        $this->authTokenRequest = $authTokenRequest;
    }//end __construct()

    /**
     * Get auth token singleton
     *
     * @return string
     * @throws \Credova\Payments\Exception\ApiException
     */
    public function getAuthToken() : string
    {
        if (is_null($this->authToken)) {
            $this->authToken = $this->authTokenRequest->getToken();
        }

        return $this->authToken;
    }//end getAuthToken()
}//end class
