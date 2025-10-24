<?php

namespace PublicSquare\Payments\Controller\Customer;

use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\View\Result\PageFactory;
use PublicSquare\Payments\Logger\Logger;

class Card implements HttpPostActionInterface
{
    /**
     * @var PageFactory
     */
    protected PageFactory $resultPageFactory;
    private RequestInterface $request;
    private Logger $logger;

    /**
     * Constructor
     *
     * @param PageFactory $resultPageFactory
     */
    public function __construct(PageFactory $resultPageFactory, Logger $logger, RequestInterface $request)
    {
        $this->resultPageFactory = $resultPageFactory;
        $this->logger = $logger;
        $this->request = $request;
    }

    public function execute(): ResultInterface
    {
        $this->logger->info("Card controller execute() with request {$this->request->getActionName()}");
        $params = $this->request->getParams();
//        $destination = $params['destination'] || $this->request->getHeader('HTTP_REFERER');

        $cardId = $params['card_id'];
        $this->logger->info("Preparing to save card id {$cardId} to vault");

        // What actually gets saved to the vault?

        // redirect to settings... or a destination from params...
        return $this->resultPageFactory->create();
    }
}