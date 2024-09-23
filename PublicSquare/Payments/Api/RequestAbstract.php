<?php
/**
 * RequestAbstract
 *
 * @category  PublicSquare
 * @package   PublicSquare_Payments
 * @author    PublicSquare <info@publicsquare.com>
 * @copyright 2024 PublicSquare
 * @license   http://opensource.org/licenses/osl-3.0.php (OSL 3.0)
 * @link      https://publicsquare.com/
 */

namespace PublicSquare\Payments\Api;

use PublicSquare\Payments\Exception\ApiException;

abstract class RequestAbstract
{
    const CONTENT_TYPE = 'application/json';

    /**
     * @var \Laminas\Http\ClientFactory
     */
    protected $clientFactory;

    /**
     * @var \PublicSquare\Payments\Helper\Config
     */
    protected $configHelper;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * @var string
     */
    protected $logPrefix;

    /**
     * @var array
     */
    protected $data = [];

    /**
     * RequestAbstract constructor.
     *
     * @param \Laminas\Http\ClientFactory         $clientFactory
     * @param \PublicSquare\Payments\Helper\Config $configHelper
     * @param \Psr\Log\LoggerInterface         $logger
     */
    public function __construct(
        \Laminas\Http\ClientFactory $clientFactory,
        \PublicSquare\Payments\Helper\Config $configHelper,
        \Psr\Log\LoggerInterface $logger
    ) {
        $this->clientFactory = $clientFactory->create();
        $this->clientFactory = $clientFactory;
        $this->configHelper  = $configHelper;
        $this->logger        = $logger;
    } //end __construct()

    /**
     * Get request path
     *
     * @return string
     */
    abstract protected function getPath(): string;

    /**
     * Get request method
     *
     * @return string
     */
    abstract protected function getMethod(): string;

    /**
     * Assemble full URI, taking care of any leading or trailing slashes
     *
     * @return string
     */
    protected function getUri(): string
    {
        $host = $this->configHelper->getUrii();

        $path = ltrim($this->getPath());

        return $host . '/' . $path;
    } //end getUri()
    
    protected function getUrii(): string
    {
        return $this->configHelper->getUrii();
    } //end getUrii()

    /**
     * Get request data, if applicable
     *
     * @return array
     */
    public function getData(): array
    {
        return $this->data;
    } //end getData()

    /**
     * Set request data, if applicable
     *
     * @param array $data
     */
    public function setData(array $data)
    {
        $this->data = $data;
    } //end setData()

    /**
     * Perform any request preparation prior to getting response
     *
     * @param \Laminas\Http\Client $client
     */
    protected function prepareRequest(\Laminas\Http\Client $client)
    {
        // This page intentionally left blank.
    } //end prepareRequest()

    /**
     * Get request headers array
     *
     * @return array
     */
    protected function getHeaders(): array
    {
        $cl_data = $this->getData();
        if (isset($cl_data['callBackUrl'])) {
            $redirect = $cl_data['callBackUrl'];
        } else {
            $redirect = "";
        }
        $is_ssl = $this->is_ssl();
        if ($redirect) {
            $headers = [
                'Content-Type' => static::CONTENT_TYPE,
                'Callback-Url' => $redirect,
            ];
        } else {
            $headers = [
                'Content-Type' => static::CONTENT_TYPE,
            ];
        }

        return $headers;
    } //end getHeaders()

    /**
     * Make request and get response
     *
     * @return \Laminas\Http\Response
     * @throws \PublicSquare\Payments\Exception\ApiException
     */
    public function getResponse(): \Laminas\Http\Response
    {

        /*
        @var \Laminas\Http\Client $client
         */
        $client = $this->clientFactory->create();
        $this->getUri();
        $client->setUri($this->getUri());
        $client->setOptions(
            ['timeout' => 30]
        );
        $client->setMethod($this->getMethod());
        $client->setHeaders($this->getHeaders());
        $crdvLog=[];

        if (!empty($this->getData())) {
            $requestBody = json_encode($this->getData());
            $crdvLog["step"] = "=============== S T E P - 1 ===============\n";
            $crdvLog["request_data"] = $this->getData();
            $client->setRawBody($requestBody);
        }

        $this->prepareRequest($client);

        /*
        @var \Laminas\Http\Response $response
         */
        $response = $client->send();

        $data = json_decode($response->getBody(), true);
        if (is_null($data)) {
            throw new \Exception("Something went wrong with the PublicSquare api. Status code: ".$response->getStatusCode()." ".implode(',', $this->getHeaders()));
        } else if (array_key_exists("publicId", $data)) {
            $crdvLog["response_code"] = $response->getStatusCode();
            $crdvLog["response_data"] = $data;
            if (isset($crdvLog["step"])) {
                $this->debugLog($crdvLog["step"]."\n".json_encode($crdvLog["request_data"])."Response Code - ".$crdvLog["response_code"]."\nResponse Data - \n".json_encode($data));
            }
        }

        return $response;
    } //end getResponse()

    /**
     * Get decoded response data
     *
     * @return array
     * @throws \PublicSquare\Payments\Exception\ApiException
     */
    public function getResponseData(): array
    {
        $response = $this->getResponse();

        $data = json_decode($response->getBody(), true);

        if (is_null($data) && json_last_error() !== JSON_ERROR_NONE) {
            throw new ApiException(__('Error decoding PublicSquare response body: %1', json_last_error()));
        }

        return $data;
    } //end getResponseData()

    public function is_ssl()
    {
        if (isset($_SERVER['HTTPS'])) {
            if ('on' == strtolower($_SERVER['HTTPS'])) {
                return true;
            }
            if ('1' == $_SERVER['HTTPS']) {
                return true;
            }
        } elseif (isset($_SERVER['SERVER_PORT']) && ('443' == $_SERVER['SERVER_PORT'])) {
            return true;
        }
        return false;
    }

    /**
     * If enabled, log debug info.
     *
     * @param string $message
     */
    protected function debugLog(string $message)
    {
        if (!$this->configHelper->getLoggingEnabled()) {
            return;
        }

        $message = $message;

        $this->logger->debug($message);
    } //end debugLog()
} //end class
