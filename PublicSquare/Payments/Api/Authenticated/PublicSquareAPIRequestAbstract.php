<?php
/**
 * AuthenticatedRequestAbstract
 *
 * @category  PublicSquare
 * @package   PublicSquare_Payments
 * @author    PublicSquare <info@publicsquare.com>
 * @copyright 2024 PublicSquare
 * @license   http://opensource.org/licenses/osl-3.0.php (OSL 3.0)
 * @link      https://publicsquare.com/
 */

namespace PublicSquare\Payments\Api\Authenticated;

abstract class PublicSquareAPIRequestAbstract extends \PublicSquare\Payments\Api\RequestAbstract
{
    /**
     * AuthenticatedRequestAbstract constructor.
     *
     * @param \Laminas\Http\ClientFactory         $clientFactory
     * @param \PublicSquare\Payments\Helper\Config $configHelper
     * @param \PublicSquare\Payments\Logger\Logger        $logger
     */
     protected $clientFactory;
     protected $configHelper;
     protected $logger;

    public function __construct(
        \Laminas\Http\ClientFactory $clientFactory,
        \PublicSquare\Payments\Helper\Config $configHelper,
        \PublicSquare\Payments\Logger\Logger $logger,
    ) {
        parent::__construct(
            $clientFactory,
            $configHelper,
            $logger
        );
    }//end __construct()
    /**
     * Add authentication header
     *
     * {@inheritdoc}
     *
     * @throws \PublicSquare\Payments\Exception\ApiException
     */
    protected function getHeaders() : array
    {
        $headers = parent::getHeaders();

        $headers['X-API-KEY'] = $this->configHelper->getSecretAPIKey();

        return $headers;
    }//end getHeaders()
}//end class
