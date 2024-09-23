<?php
/**
 * Returns Interface
 *
 * @category  PublicSquare
 * @package   PublicSquare_Payments
 * @author    PublicSquare <info@publicsquare.com>
 * @copyright 2024 PublicSquare
 * @license   http://opensource.org/licenses/osl-3.0.php (OSL 3.0)
 * @link      https://publicsquare.com/
 */

namespace PublicSquare\Payments\Api;

interface RefundsInterface
{
     
     /**
      * Returns an application in PublicSquare and returns the public id
      *
      * @param  string $transactionId
      * @return string
      * @throws \Magento\Framework\Exception\LocalizedException
      */
    public function refundOrder($transactionId);
}//end interface
