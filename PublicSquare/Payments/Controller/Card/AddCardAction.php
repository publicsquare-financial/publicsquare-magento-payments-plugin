<?php

namespace PublicSquare\Payments\Controller\Card;

use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\View\Result\PageFactory;

class AddCardAction extends \Magento\Framework\App\Action\Action
{
    /**
     * @var PageFactory
     */
    protected PageFactory $resultPageFactory;

    /**
     * Constructor
     *
     * @param PageFactory $resultPageFactory
     */
    public function __construct(PageFactory $resultPageFactory)
    {
        $this->resultPageFactory = $resultPageFactory;
    }

    public function execute(): ResultInterface
    {
        $params = $this->getRequest()->getParams();
        $destination = $params['destination'] || $this->getRequest()->getHeader('HTTP_REFERER');

        // read card info & save to psq and vault

        return $this->resultPageFactory->create();
    }
}