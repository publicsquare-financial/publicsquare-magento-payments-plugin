<?php
/**
 * AuthenticatedRequestAbstract
 *
 * @category  Credova
 * @package   Credova_Payments
 * @author    Credova <info@credova.com>
 * @copyright 2024 Credova
 * @license   http://opensource.org/licenses/osl-3.0.php (OSL 3.0)
 * @link      https://credova.com/
 */

namespace Credova\Payments\Api\Authenticated;

abstract class AuthenticatedRequestAbstract extends \Credova\Payments\Api\RequestAbstract
{
    /**
     * @var \Credova\Payments\Helper\Api
     */
    protected $apiHelper;
    /**
     * AuthenticatedRequestAbstract constructor.
     *
     * @param \Laminas\Http\ClientFactory         $clientFactory
     * @param \Credova\Payments\Helper\Config $configHelper
     * @param \Psr\Log\LoggerInterface         $logger
     * @param \Credova\Payments\Helper\Api    $apiHelper
     */

     protected $clientFactory;
     protected $configHelper;
     protected $logger;

    public function __construct(
        \Laminas\Http\ClientFactory $clientFactory,
        \Credova\Payments\Helper\Config $configHelper,
        \Psr\Log\LoggerInterface $logger,
        \Credova\Payments\Helper\Api $apiHelper
    ) {
        parent::__construct(
            $clientFactory,
            $configHelper,
            $logger
        );
        $this->apiHelper = $apiHelper;
    }//end __construct()
    /**
     * Add authentication header
     *
     * {@inheritdoc}
     *
     * @throws \Credova\Payments\Exception\ApiException
     */
    protected function getHeaders() : array
    {
        $headers = parent::getHeaders();

        $authToken = $this->apiHelper->getAuthToken();

        $headers['X-API-KEY'] = $authToken;

        return $headers;
    }//end getHeaders()
}//end class
