<?php

namespace PublicSquare\Payments\Services;

use Magento\AdminNotification\Model\ResourceModel\Inbox\CollectionFactory;
use Magento\Framework\Notification\NotifierInterface;
use phpseclib3\Crypt\PublicKeyLoader;
use phpseclib3\Crypt\RSA;
use PublicSquare\Payments\Exception\ErrorNotifications;
use PublicSquare\Payments\Exception\NotConfiguredException;
use PublicSquare\Payments\Helper\Config;
use PublicSquare\Payments\Logger\Logger;

class WebhookSignatureService
{

    private Config $config;

    private Logger $logger;
    private NotifierInterface $notifier;
    private CollectionFactory $collectionFactory;

    public function __construct(
        Config $config,
        Logger $logger,
        NotifierInterface $notifier,
        CollectionFactory $collectionFactory,
    ) {
        $this->config = $config;
        $this->logger = $logger->withName('PSQ:WebhookSignatureService');
        $this->notifier = $notifier;
        $this->collectionFactory = $collectionFactory;
    }

    public function verify(string $signature, string $body): bool {
        $webhookKey = $this->config->getWebhookKey();
        if (!$webhookKey) {
            // If we are receiving webhook requests but have not configured the key
            // we need to notify.
            $collection = $this->collectionFactory->create();
            $exists = $collection->addFieldToFilter('title', ErrorNotifications::WEBHOOK_KEY_MISSING)->addRemoveFilter()->getSize() > 0;
            if (!$exists) {
                $description = 'The webhook key for PublicSquare payments is not configured. Please set it in Stores > Configuration > Sales > Payment Methods > PublicSquare.';
                $this->notifier->addNotice(ErrorNotifications::WEBHOOK_KEY_MISSING, $description);
            }
            $this->logger->error('Webhook key not configured');
            throw new NotConfiguredException(Config::PUBLICSQUARE_WEBHOOK_KEY, 'Webhook Key');
        }


        try {
            if(preg_match('/^-+BEGIN\sPUBLIC\sKEY-+.*$/' , $webhookKey)) {
                $publicKeyPem = $webhookKey;
            } else {
                $publicKeyPem = "-----BEGIN PUBLIC KEY-----\r\n" .
                    chunk_split($webhookKey, 64, "\r\n") .
                    "-----END PUBLIC KEY-----\r\n";
            }
            $rsa = PublicKeyLoader::load($publicKeyPem);
            $rsa = $rsa->withPadding(RSA::SIGNATURE_PKCS1)->withHash('sha256');
            $verified = $rsa->verify($body, base64_decode($signature));
        } catch (\Exception $e) {
            $this->logger->error('PSQ Webhook: Invalid public key or verification error', ['exception' => $e->getMessage()]);
            return false;
        }

        return $verified;
    }

}