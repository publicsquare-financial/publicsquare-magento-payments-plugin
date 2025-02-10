<?php

/**
 * The purpose of this plugin is to add the cardId to the additional_information array
 * so that it can be used by the gateway to process payments.
 */

namespace PublicSquare\Payments\Plugins;

use PublicSquare\Payments\Logger\Logger;

class QuotePaymentPlugin
{
  /**
   * @var Logger
   */
  private $logger;

  public function __construct(Logger $logger)
  {
    $this->logger = $logger;
  }

  /**
   * @param \Magento\Quote\Model\Quote\Payment $subject
   * @param array $data
   * @return array
   */
  public function beforeImportData(\Magento\Quote\Model\Quote\Payment $subject, array $data)
  {
    if (array_key_exists('additional_data', $data)) {
      $subject->setAdditionalInformation($data['additional_data']);
    }
    return [$data];
  }
}