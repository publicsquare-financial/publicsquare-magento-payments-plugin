<?php
/**
 * Payments Interface
 *
 * @category  Credova
 * @package   Credova_Payments
 * @author    Credova <info@credova.com>
 * @copyright 2024 Credova
 * @license   http://opensource.org/licenses/osl-3.0.php (OSL 3.0)
 * @link      https://credova.com/
 */

namespace Credova\Payments\Api;

interface PaymentsInterface
{
     /**
      * Creates a payment in Credova and returns the public id
      *
      * @param  \Credova\Payments\Api\Data\CustomerInterface $customer
      * @return string
      * @throws \Magento\Framework\Exception\LocalizedException
      */
    public function createPayment($customer);
}//end interface
