<?php

namespace PublicSquare\Payments\Api\Authenticated;

use Aws\finspace\Exception\finspaceException;
use PublicSquare\Payments\Logger\Logger;

class PSQCurlClient
{
    private Logger $logger;
    private string $baseUrl;

    function __construct(Logger $logger, string $baseUrl = "https://api.publicsquare.com")
    {
        $this->baseUrl = $baseUrl;
        $this->logger = $logger->withName('PSQ:CurlClient');
    }


    /**
     * @throws \Exception
     */
    public function createWebhook(string $privateKey, string $webhookUrl): array
    {
        $req = curl_init($this->baseUrl . '/webhooks');

        $body = json_encode([
            'url' => $webhookUrl,
            'event_types' => [
                'settlement:update',
                'refund:update',
            ],
        ]);
        curl_setopt($req, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($req, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($req, CURLOPT_POSTFIELDS, $body);
        curl_setopt($req, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Content-Length: ' . strlen($body),
            'Accept: application/json',
            'X-API-KEY: ' . $privateKey,

        ]);
        try {
            $this->logger->debug('Creating webhook for url: ' . $webhookUrl);
            $response = curl_exec($req);
            $responseBody = json_decode($response, true);
            return $responseBody;
        } catch (\Exception $err) {
            $this->logger->warning( 'Failed to create webhook: ' . $err->getMessage());
            throw $err;
        } finally {
            curl_close($req);
        }
    }

    /**
     * @throws \Exception
     */
    public function getWebhook(string $privateKey, string $webhookId): array
    {
        $req = curl_init($this->baseUrl . '/webhooks/' . $webhookId);


        curl_setopt($req, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($req, CURLOPT_HTTPHEADER, [
            'Accept: application/json',
            'X-API-KEY: ' . $privateKey,
        ]);
        try {
            $this->logger->debug('GET webhook ID: ' . $webhookId);
            $response = curl_exec($req);
            $responseBody = json_decode($response, true);
            $this->logger->info('Retrieved webhook with ID: ' . $responseBody['id']);
            return $responseBody;
        } catch (\Exception $err) {
            $this->logger->error( 'Error getting webhook with id [' . $webhookId . ']: ' . $err->getMessage());
            throw $err;
        } finally {
            curl_close($req);
        }
    }
}