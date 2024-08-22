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

use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\CsrfAwareActionInterface;

interface OrdersInterface extends HttpPostActionInterface, CsrfAwareActionInterface
{
     /**
      * Creates a payment in Credova and returns the public id
      *
      * @param string $cardId
      * @return string
      * @throws \Magento\Framework\Exception\LocalizedException
      */
    public function create($cardId);
}//end interface
