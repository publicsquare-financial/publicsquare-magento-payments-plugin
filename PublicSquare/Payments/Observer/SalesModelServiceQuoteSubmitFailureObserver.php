<?php

namespace PublicSquare\Payments\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use PublicSquare\Payments\Gateway\PaymentExecutor;

class SalesModelServiceQuoteSubmitFailureObserver implements ObserverInterface
{
  /**
   * @var \PublicSquare\Payments\Gateway\PaymentExecutor
   */
  private $paymentExecutor;

  public function __construct(
    PaymentExecutor $paymentExecutor
  ) {
    $this->paymentExecutor = $paymentExecutor;
  }

  /**
   * @param Observer $observer
   * @return void
   */
  public function execute(Observer $observer)
  {
    $this->paymentExecutor->executeUpdateFromObserver($observer);
    return $this;
  }
}
