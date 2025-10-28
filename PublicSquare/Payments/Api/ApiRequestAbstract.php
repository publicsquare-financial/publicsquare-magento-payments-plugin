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
use PublicSquare\Payments\Exception\ApiRejectedResponseException;
use PublicSquare\Payments\Exception\ApiDeclinedResponseException;
use PublicSquare\Payments\Exception\ApiFailedResponseException;

abstract class ApiRequestAbstract
{
    const CONTENT_TYPE = 'application/json';
    const REJECTED_STATUS = 'rejected';
    const DECLINED_STATUS = 'declined';
    const FAILED_STATUS = 'failed';
    const SUCCEEDED_STATUS = 'succeeded';
    const REQUIRES_CAPTURE_STATUS = 'requires_capture';
    const CANCELLED_STATUS = 'cancelled';

    /**
     * @var \Laminas\Http\ClientFactory
     */
    protected $clientFactory;

    /**
     * @var \PublicSquare\Payments\Helper\Config
     */
    protected $configHelper;

    /**
     * @var \PublicSquare\Payments\Logger\Logger
     */
    protected $logger;

    /**
     * @var string
     */
    protected $logPrefix;

    /**
     * @var string
     */
    protected $objectId;

    /**
     * @var array
     */
    protected $requestData;

    /**
     * @var array
     */
    protected $responseData;

    /**
     * @var \Laminas\Http\Response
     */
    protected $response;

    /**
     * @var string
     */
    protected $idempotencyKey;

    /**
     * RequestAbstract constructor.
     *
     * @param \Laminas\Http\ClientFactory           $clientFactory
     * @param \PublicSquare\Payments\Helper\Config  $configHelper
     * @param \PublicSquare\Payments\Logger\Logger  $logger
     */
    public function __construct(
        \Laminas\Http\ClientFactory $clientFactory,
        \PublicSquare\Payments\Helper\Config $configHelper,
        \PublicSquare\Payments\Logger\Logger $logger
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

        $uri = $host . '/' . $path;

        if ($this->objectId) {
            $uri .= '/' . $this->objectId;
        }

        return $uri;
    } //end getUri()

    protected function getUrii(): string
    {
        return $this->configHelper->getUrii();
    } //end getUrii()

    /**
     * Set request response, if applicable
     *
     * @param \Laminas\Http\Response $response
     */
    public function setResponse(\Laminas\Http\Response $response)
    {
        $this->response = $response;
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
     * Validate the response was successful
     *
     * @return bool
     */
    abstract protected function validateResponse(mixed $data): bool;

    /**
     * Get request headers array
     *
     * @return array
     */
    protected function getHeaders(): array
    {
        $cl_data = $this->requestData;
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

        $headers['X-API-KEY'] = $this->configHelper->getSecretAPIKey();

        if (!empty($this->idempotencyKey)) {
            $headers['IDEMPOTENCY-KEY'] = substr($this->idempotencyKey, 0, 50);
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
        if (!isset($this->response)) {
            // OFFLINE MOCK: never hit the network when enabled
            if (method_exists($this->configHelper, 'isApiMockEnabled') && $this->configHelper->isApiMockEnabled()) {
                $this->responseData = $this->buildMockResponse();
                $response = new \Laminas\Http\Response();
                $response->setStatusCode(200);
                $response->setContent(json_encode($this->responseData));
                $this->setResponse($response);

                // Let normal validation/error paths run
                if (is_null($this->responseData)) {
                    throw new \Exception("Something went wrong. Please try again.");
                } else {
                    $this->validateResponse($this->responseData);
                }
                return $this->response;
            }

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

            if (!empty($this->requestData)) {
                $requestBody = json_encode($this->requestData);
                $client->setRawBody($requestBody);
            }

            $this->prepareRequest($client);

            /*
            @var \Laminas\Http\Response $response
            */
            $response = $client->send();
            $this->setResponse($response);
            $this->responseData = json_decode($this->response->getBody(), true);

            if (is_null($this->responseData)) {
                throw new \Exception("Something went wrong. Please try again.");
            } else {
                $this->validateResponse($this->responseData);
            }
        }
        return $this->response;
    } //end getResponse()

    /**
     * Get decoded response data
     *
     * @return array
     * @throws \PublicSquare\Payments\Exception\ApiException
     */
    public function getResponseData(): array
    {
        $this->getResponse();

        $data = $this->responseData;

        if (is_null($data) && json_last_error() !== JSON_ERROR_NONE) {
            throw new ApiException(__('Error decoding response body: %1', json_last_error()));
        }

        return $data;
    } //end getResponseData()

    /**
     * Get sanitized response data without PII
     *
     * @return array
     */
    public function getSanitizedResponseData(): array
    {
        return array_intersect_key($this->getResponseData(), array_flip(['id', 'errors', 'fraud_details', 'status', 'environment', 'transaction_id', 'amount', 'amount_capturable', 'account_id', 'refunded', 'declined_reason', 'capture', 'currency', 'external_id']));
    } //end getSanitizedResponseData()

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

    public function checkResponseStatus($responseData): bool {
        if ($responseData['status'] === self::REJECTED_STATUS) {
            throw new ApiRejectedResponseException("Something went wrong. Please try again.");
        } else if ($responseData['status'] === self::DECLINED_STATUS) {
            throw new ApiDeclinedResponseException("Something went wrong. Please try again.");
        } else if ($responseData['status'] === self::FAILED_STATUS) {
            throw new ApiFailedResponseException("Something went wrong. Please try again.");
        }
        return true;
    }

    /**
     * Build mock response data based on request parameters
     *
     * @return array
     */
    protected function buildMockResponse(): array
    {
        $cardId = $this->requestData['payment_method']['card'] ?? '';
        $amount = $this->requestData['amount'] ?? 0;
        $capture = (bool)($this->requestData['capture'] ?? true);

        $last4 = substr(preg_replace('/[^0-9]/', '', (string)$cardId), -4);

        switch ($cardId) {
            case '4242424242424242':
            case 'card_mock_4242':
                return [
                    'id' => 'pmt_mock_success_4242',
                    'status' => 'succeeded',
                    'amount' => $amount,
                    'amount_capturable' => $capture ? 0 : $amount,
                    'currency' => 'USD',
                    'capture' => $capture,
                    'fraud_details' => [
                        'decision' => 'accept',
                        'rules' => []
                    ],
                    'payment_method' => [
                        'card' => [
                            'id' => 'card_mock_4242',
                            'last4' => '4242',
                            'brand' => 'visa',
                            'exp_month' => 12,
                            'exp_year' => 2029,
                            'avs_code' => 'Y',
                            'cvv2_reply' => 'M',
                        ],
                    ],
                ];
            case '4000000000000002':
            case 'card_mock_0002':
                return [
                    'id' => 'pmt_mock_decline',
                    'status' => 'declined',
                    'declined_reason' => 'Decline',
                    'amount' => $amount,
                    'currency' => 'USD',
                    'fraud_details' => [
                        'decision' => 'reject',
                        'rules' => ['Mock Decline Rule']
                    ],
                    'payment_method' => [
                        'card' => [
                            'id' => 'card_mock_dec',
                            'last4' => '0002',
                            'brand' => 'visa',
                            'exp_month' => 12,
                            'exp_year' => 2029,
                            'avs_code' => 'N',
                            'cvv2_reply' => 'N',
                        ],
                    ],
                ];
            case '4000000000009995':
            case 'card_mock_9995':
                return [
                    'id' => 'pmt_mock_insufficient',
                    'status' => 'declined',
                    'declined_reason' => 'Insufficient Funds',
                    'amount' => $amount,
                    'currency' => 'USD',
                    'fraud_details' => [
                        'decision' => 'reject',
                        'rules' => ['Mock Insufficient Funds Rule']
                    ],
                    'payment_method' => [
                        'card' => [
                            'id' => 'card_mock_insufficient',
                            'last4' => '9995',
                            'brand' => 'visa',
                            'exp_month' => 12,
                            'exp_year' => 2029,
                            'avs_code' => 'N',
                            'cvv2_reply' => 'N',
                        ],
                    ],
                ];
            case '4000000000009987':
            case 'card_mock_9987':
                return [
                    'id' => 'pmt_mock_lost_stolen',
                    'status' => 'declined',
                    'declined_reason' => 'Lost/Stolen',
                    'amount' => $amount,
                    'currency' => 'USD',
                    'fraud_details' => [
                        'decision' => 'reject',
                        'rules' => ['Mock Lost/Stolen Rule']
                    ],
                    'payment_method' => [
                        'card' => [
                            'id' => 'card_mock_lost_stolen',
                            'last4' => '9987',
                            'brand' => 'visa',
                            'exp_month' => 12,
                            'exp_year' => 2029,
                            'avs_code' => 'N',
                            'cvv2_reply' => 'N',
                        ],
                    ],
                ];
            case '4100000000000019':
            case 'card_mock_0019':
                return [
                    'id' => 'pmt_mock_rejected',
                    'status' => 'rejected',
                    'amount' => $amount,
                    'currency' => 'USD',
                    'fraud_details' => [
                        'decision' => 'reject',
                        'rules' => ['Mock Fraud Rule']
                    ],
                    'payment_method' => [
                        'card' => [
                            'id' => 'card_mock_rejected',
                            'last4' => '0019',
                            'brand' => 'visa',
                            'exp_month' => 12,
                            'exp_year' => 2029,
                            'avs_code' => 'N',
                            'cvv2_reply' => 'N',
                        ],
                    ],
                ];
            case '4000000000000101':
            case 'card_mock_0101':
                return [
                    'id' => 'pmt_mock_cvc_fail',
                    'status' => 'rejected',
                    'amount' => $amount,
                    'currency' => 'USD',
                    'fraud_details' => [
                        'decision' => 'reject',
                        'rules' => ['Mock CVC Rule']
                    ],
                    'payment_method' => [
                        'card' => [
                            'id' => 'card_mock_cvc_fail',
                            'last4' => '0101',
                            'brand' => 'visa',
                            'exp_month' => 12,
                            'exp_year' => 2029,
                            'avs_code' => 'N',
                            'cvv2_reply' => 'N',
                        ],
                    ],
                ];
            case '4000000000000010':
            case 'card_mock_0010':
                return [
                    'id' => 'pmt_mock_avs_fail',
                    'status' => 'rejected',
                    'amount' => $amount,
                    'currency' => 'USD',
                    'fraud_details' => [
                        'decision' => 'reject',
                        'rules' => ['Mock AVS Rule']
                    ],
                    'payment_method' => [
                        'card' => [
                            'id' => 'card_mock_avs_fail',
                            'last4' => '0010',
                            'brand' => 'visa',
                            'exp_month' => 12,
                            'exp_year' => 2029,
                            'avs_code' => 'N',
                            'cvv2_reply' => 'N',
                        ],
                    ],
                ];
            case '4111111111111111':
            case 'card_mock_1111':
                return [
                    'id' => 'pmt_mock_failed',
                    'status' => 'failed',
                    'amount' => $amount,
                    'currency' => 'USD',
                    'fraud_details' => [
                        'decision' => 'reject',
                        'rules' => ['Mock Failure Rule']
                    ],
                    'payment_method' => [
                        'card' => [
                            'id' => 'card_mock_failed',
                            'last4' => '1111',
                            'brand' => 'visa',
                            'exp_month' => 12,
                            'exp_year' => 2029,
                            'avs_code' => 'N',
                            'cvv2_reply' => 'N',
                        ],
                    ],
                ];
            default:
                return [
                    'id' => 'pmt_mock_generic_failed',
                    'status' => $last4 === '4242' ? 'succeeded' : 'failed',
                    'amount' => $amount,
                    'currency' => 'USD',
                    'fraud_details' => [
                        'decision' => $last4 === '4242' ? 'accept' : 'reject',
                        'rules' => $last4 === '4242' ? [] : ['Mock Generic Rule']
                    ],
                    'payment_method' => [
                        'card' => [
                            'id' => 'card_mock_generic',
                            'last4' => $last4,
                            'brand' => 'visa',
                            'exp_month' => 12,
                            'exp_year' => 2029,
                            'avs_code' => $last4 === '4242' ? 'Y' : 'N',
                            'cvv2_reply' => $last4 === '4242' ? 'M' : 'N',
                        ],
                    ],
                ];
        }
    }
} //end class
