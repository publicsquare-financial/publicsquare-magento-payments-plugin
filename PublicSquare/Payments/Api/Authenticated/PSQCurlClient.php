<?php

namespace PublicSquare\Payments\Api\Authenticated;

use Laminas\Http\Client;
use Laminas\Http\Request;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use PublicSquare\Payments\Helper\Config;
use PublicSquare\Payments\Logger\Logger;

class PSQCurlClient
{
    private Logger $logger;
    private string $baseUrl;

    public function __construct(
        Logger $logger,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->baseUrl = $scopeConfig->getValue(
            Config::PUBLICSQUARE_API_BASE_URL,
            ScopeInterface::SCOPE_STORE,
            null
        ) ?? "https://api.publicsquare.com";
        $this->logger = $logger->withName('PSQ:CurlClient');
    }


    /**
     * @throws \Exception
     */
    public function createWebhook(string $privateKey, string $webhookUrl): array
    {
        $client = new Client();
        $client->setUri($this->baseUrl . '/webhooks');
        $client->setMethod(Request::METHOD_POST);

        $body = json_encode([
            'url' => $webhookUrl,
            'event_types' => [
                'settlement:update',
                'refund:update',
            ],
        ]);

        $client->setRawBody($body);
        $client->setHeaders([
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'X-API-KEY' => $privateKey,
        ]);

        try {
            $this->logger->debug('Creating webhook for url: ' . $webhookUrl);
            $response = $client->send();
            $responseBody = json_decode($response->getBody(), true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->logger->error('Failed to decode createWebhook response', ['response' => $response->getBody()]);
                throw new \Exception('Failed to decode API response.');
            }
            return $responseBody;
        } catch (\Exception $err) {
            $this->logger->warning('Failed to create webhook: ' . $err->getMessage());
            throw $err;
        }
    }

    /**
     * @throws \Exception
     */
    public function getWebhook(string $privateKey, string $webhookId): array
    {
        $client = new Client();
        $client->setUri($this->baseUrl . '/webhooks/' . $webhookId);
        $client->setMethod(Request::METHOD_GET);

        $client->setHeaders([
            'Accept' => 'application/json',
            'X-API-KEY' => $privateKey,
        ]);

        try {
            $this->logger->debug('GET webhook ID: ' . $webhookId);
            $response = $client->send();
            $responseBody = json_decode($response->getBody(), true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->logger->error('Failed to decode getWebhook response', ['response' => $response->getBody()]);
                throw new \Exception('Failed to decode API response.');
            }
            $this->logger->info('Retrieved webhook with ID: ' . $responseBody['id']);
            return $responseBody;
        } catch (\Exception $err) {
            $this->logger->error('Error getting webhook with id [' . $webhookId . ']: ' . $err->getMessage());
            throw $err;
        }
    }
}