<?php
/**
 * Application
 *
 * @category  Credova
 * @package   Credova_Payments
 * @author    Credova <info@credova.com>
 * @copyright 2024 Credova
 * @license   http://opensource.org/licenses/osl-3.0.php (OSL 3.0)
 * @link      https://credova.com/
 */

namespace Credova\Payments\Api\Authenticated;

class Refunds extends AuthenticatedRequestAbstract
{
    const PATH = 'refunds';

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
        \Credova\Payments\Helper\Config $configHelper,
        \Psr\Log\LoggerInterface $logger,
        array $refund = []
    ) {
        parent::__construct($clientFactory, $configHelper, $logger);
        $this->data = $refund;
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

    public function getData(): array
    {
        return $this->data;
    }//end getData()
}//end class