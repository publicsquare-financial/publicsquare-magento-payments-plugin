<?php

namespace PublicSquare\Payments\Api\Authenticated;

use Laminas\Http\Client;
use Laminas\Http\Request;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use PublicSquare\Payments\Helper\Config;
use PublicSquare\Payments\Logger\Logger;

class SettlementClient
{
    private Logger $logger;
    private string $baseUrl;
    private  string|null $privateKey;

    public function __construct(
        Logger $logger,
        ScopeConfigInterface $scopeConfig,
    ) {
        $this->baseUrl = $scopeConfig->getValue(
            Config::PUBLICSQUARE_API_BASE_URL,
            ScopeInterface::SCOPE_STORE,
            null
        ) ?? "https://api.publicsquare.com";
        $this->privateKey = $scopeConfig->getValue(
            Config::PUBLICSQUARE_API_SECRET_KEY,
        );
        $this->logger = $logger->withName('PSQ:SettlementClient');
    }
    private function configurationRequired(): void {
        if(empty($this->privateKey)) {
            $this->logger->warning('Missing secret key for PublicSquare APIs');
            throw new \RuntimeException('Private key not configured');
        }
    }

    /**
     * @throws \Exception
     */
    public function search(
        string|null $settlement_id = null,
        bool|null $include_type = null,
        string|null $start_dt = null,
        string|null $end_dt = null,
        array|null $status = null,
        string|null $query = null,
        int $page = 1,
        int $size = 100,
    ): array
    {
        $this->configurationRequired();
        try {
            $client = new Client();
            $client->setUri($this->baseUrl . '/settlements');
            $client->setMethod(Request::METHOD_GET);
            $query = array_filter(
                compact('settlement_id', 'include_type', 'start_dt', 'end_dt', 'status', 'query', 'page', 'size'),
                static function ($value, $key) {
                    if($value === null) {
                        return false;
                    }

                    if(is_string($value) || is_array($value)) {
                        return !empty($value);
                    }

                    return true;
                },
                ARRAY_FILTER_USE_BOTH,
            );
            $client->setParameterGet($query);


            $client->setHeaders([
                'Accept' => 'application/json',
                'X-API-KEY' => $this->privateKey,
            ]);
            $this->logger->debug('Refund search');
            $response = $client->send();
            return json_decode($response->getBody(), true, 512, JSON_THROW_ON_ERROR);
        } catch (\Exception $err) {
            $this->logger->warning('Failed to search settlements: ' . $err->getMessage(), compact('err'));
            throw $err;
        }
    }


}