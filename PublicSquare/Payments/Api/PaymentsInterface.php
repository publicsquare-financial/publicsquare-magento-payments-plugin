<?php
/**
 * Payments Interface
 *
 * @category  PublicSquare
 * @package   PublicSquare_Payments
 * @author    PublicSquare <info@publicsquare.com>
 * @copyright 2024 PublicSquare
 * @license   http://opensource.org/licenses/osl-3.0.php (OSL 3.0)
 * @link      https://publicsquare.com/
 */

namespace PublicSquare\Payments\Api;

interface PaymentsInterface
{
     /**
      * Creates a payment in PublicSquare and returns the public id
      *
      * @param string $cardId = null
      * @param bool $saveCard = false
      * @param string $publicHash = null
      * @return string
      * @throws \Magento\Framework\Exception\LocalizedException
      */
    public function createPayment($cardId = null, $saveCard = false, $publicHash = null);
}//end interface
