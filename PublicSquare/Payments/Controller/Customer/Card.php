<?php

namespace PublicSquare\Payments\Controller\Customer;

use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\View\Result\PageFactory;
use Magento\Vault\Api\Data\PaymentTokenFactoryInterface;
use Magento\Vault\Api\PaymentTokenRepositoryInterface;
use PublicSquare\Payments\Logger\Logger;

class Card implements HttpPostActionInterface
{
    /**
     * @var PageFactory
     */
    private ResultFactory $resultFactory;
    private RequestInterface $request;
    private Logger $logger;

    private PaymentTokenFactoryInterface $paymentTokenFactory;
    private PaymentTokenRepositoryInterface $paymentTokenRepository;

    private \Magento\Customer\Model\Session $customerSession;
    private ManagerInterface $messageManager;

    private EncryptorInterface $encryptor;

    /**
     * @param ResultFactory $resultFactory
     * @param Logger $logger
     * @param RequestInterface $request
     * @param PaymentTokenFactoryInterface $paymentTokenFactory
     * @param PaymentTokenRepositoryInterface $paymentTokenRepository
     * @param \PublicSquare\Payments\Helper\Config $psqConfig
     * @param \Magento\Customer\Model\Session $customerSession
     * @param ManagerInterface $messageManager
     * @param EncryptorInterface $encryptor
     */
    public function __construct(
        ResultFactory                        $resultFactory,
        Logger                               $logger,
        RequestInterface                     $request,
        PaymentTokenFactoryInterface         $paymentTokenFactory,
        PaymentTokenRepositoryInterface      $paymentTokenRepository,
        \PublicSquare\Payments\Helper\Config $psqConfig,
        \Magento\Customer\Model\Session      $customerSession,
        ManagerInterface                     $messageManager,
        EncryptorInterface                   $encryptor,
    )
    {
        $this->resultFactory = $resultFactory;
        $this->logger = $logger;
        $this->request = $request;
        $this->paymentTokenFactory = $paymentTokenFactory;
        $this->paymentTokenRepository = $paymentTokenRepository;
        $this->customerSession = $customerSession;
        $this->messageManager = $messageManager;
        $this->encryptor = $encryptor;
    }

    public function execute(): ResultInterface
    {
        $this->logger->debug("Card controller execute() with request {$this->request->getActionName()}");

        $cardId = $this->request->getPost('card_id');
        $expYear = $this->request->getPost('exp_year');
        $expMonth = $this->request->getPost('exp_month');
        $details = $this->request->getPost('details');


        $expiresAt = date_create_from_format('Y-m-d',
            sprintf(
                "%d-%d-1",
                $expYear,
                ($expMonth + 1) % 12,
            ));

        $paymentToken = $this->paymentTokenFactory->create(PaymentTokenFactoryInterface::TOKEN_TYPE_CREDIT_CARD);
        $paymentToken->setCustomerId($this->customerSession->getCustomerId());
        $paymentToken->setGatewayToken($cardId);
        $paymentToken->setPaymentMethodCode(\PublicSquare\Payments\Helper\Config::CODE);
        $paymentToken->setIsVisible(true);
        $paymentToken->setExpiresAt($expiresAt);
        $paymentToken->setWebsiteId($this->customerSession->getCustomer()->getWebsiteId());
        $paymentToken->setTokenDetails($details);

        $this->logger->debug("Payment token initialized, creating public hash");
        $publicHash = $this->encryptor->hash(implode([
            $paymentToken->getTokenDetails(),
            $paymentToken->getCustomerId(),
            $paymentToken->getWebsiteId(),
            $paymentToken->getGatewayToken(),
            date_format($paymentToken->getExpiresAt(), 'YmdHis'),
        ]));
        $paymentToken->setPublicHash($publicHash);

        try {
            $this->logger->debug("Preparing to save token to vault");

            $this->paymentTokenRepository->save($paymentToken);
            $this->logger->debug("Saved to vault with token id {$paymentToken->getId()}");

        } catch (AlreadyExistsException $e) {
            $this->logger->debug($e->getMessage());
            $this->messageManager->addErrorMessage(__("Token already exists!"));
        }
        // redirect to settings... or a destination from params...
        $result = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $result->setPath("vault/cards/listaction");

        return $result;
    }
}