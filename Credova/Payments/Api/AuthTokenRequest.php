<?php
/**
 * Auth Token Request
 *
 * @category  Credova
 * @package   Credova_Payments
 * @author    Credova <info@credova.com>
 * @copyright 2024 Credova
 * @license   http://opensource.org/licenses/osl-3.0.php (OSL 3.0)
 * @link      https://credova.com/
 */

namespace Credova\Payments\Api;

class AuthTokenRequest extends \Credova\Payments\Api\RequestAbstract
{
    const PATH      = 'v2/token';
    const TOKEN_KEY = 'jwt';
    const CACHE_KEY = 'credova.token';

    /**
     * Override content type from abstract
     */
    const CONTENT_TYPE = 'application/x-www-form-urlencoded';

    /**
     * @var Magento\Framework\App\CacheInterface
     */
    private $cache;
    protected $clientFactory;
    protected $configHelper;
    protected $logger;

    public function __construct(
        \Laminas\Http\ClientFactory $clientFactory,
        \Credova\Payments\Helper\Config $configHelper,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\App\CacheInterface $cache
    ) {
        parent::__construct($clientFactory, $configHelper, $logger);
        $this->cache = $cache;
    }//end __construct()

    /**
     * {@inheritdoc}
     */
    protected function getPath(): string
    {
        return static::PATH;
    }//end getPath()

    /**
     * {@inheritdoc}
     */
    protected function getMethod(): string
    {
        return \Laminas\Http\Request::METHOD_POST;
    }//end getMethod()

    /**
     * Add post data
     *
     * {@inheritdoc}
     */
    protected function prepareRequest(\Laminas\Http\Client $client)
    {
        parent::prepareRequest($client);

        $client->setParameterPost(
            [
                'username' => $this->configHelper->getApiUsername(),
                'password' => $this->configHelper->getApiPassword(),
            ]
        );
    }//end prepareRequest()
    
    /**
     * Get token string from response
     *
     * @return string
     * @throws \Credova\Payments\Exception\ApiException
     */
    public function getToken(): string
    {
        $token = $this->cache->load(self::CACHE_KEY);
        if ($token !== false) {
            return $token;
        }
        $data = $this->getResponseData();
        if (!isset($data[self::TOKEN_KEY])) {
            throw new \Credova\Payments\Exception\ApiException(__('Access token not found.'));
        }
        // Set the cache expiration to the exp timestamp in the token, minus 5 minutes to ensure we don't have rejected token errors
        $expirationInSeconds = (json_decode(base64_decode($data[self::TOKEN_KEY]), true) - time() - (5 * 60));

        $this->cache->save($data[self::TOKEN_KEY], self::CACHE_KEY, [], $expirationInSeconds);

        return $data[self::TOKEN_KEY];
    }//end getToken()
}//end class
