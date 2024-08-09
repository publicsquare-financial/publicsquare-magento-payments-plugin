<?php
/**
 * AuthenticatedRequestAbstract
 *
 * @category  Credova
 * @package   Credova_Financial
 * @author    Credova <info@credova.com>
 * @copyright 2019 Credova
 * @license   http://opensource.org/licenses/osl-3.0.php (OSL 3.0)
 * @link      https://credova.com/
 */

namespace Credova\Financial\Api\Authenticated;

abstract class AuthenticatedRequestAbstract extends \Credova\Financial\Api\RequestAbstract
{
    /**
     * @var \Credova\Financial\Helper\Api
     */
    protected $apiHelper;
    /**
     * AuthenticatedRequestAbstract constructor.
     *
     * @param \Laminas\Http\ClientFactory         $clientFactory
     * @param \Credova\Financial\Helper\Config $configHelper
     * @param \Psr\Log\LoggerInterface         $logger
     * @param \Credova\Financial\Helper\Api    $apiHelper
     */

     protected $clientFactory;
     protected $configHelper;
     protected $logger;

    public function __construct(
        \Laminas\Http\ClientFactory $clientFactory,
        \Credova\Financial\Helper\Config $configHelper,
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
     * @throws \Credova\Financial\Exception\ApiException
     */
    protected function getHeaders() : array
    {
        $headers = parent::getHeaders();

        $authToken = $this->apiHelper->getAuthToken();

        $headers['X-API-KEY'] = $authToken;

        return $headers;
    }//end getHeaders()
}//end class
