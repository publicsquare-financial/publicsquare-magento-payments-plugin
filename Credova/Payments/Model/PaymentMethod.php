<?php
/**
 * Credova_Payments
 *
 * @category  Credova
 * @package   Credova_Payments
 * @author    Credova <info@credova.com>
 * @copyright 2024 Credova
 * @license   http://opensource.org/licenses/osl-3.0.php (OSL 3.0)
 * @link      https://credova.com/
 */

namespace Credova\Payments\Model;

/**
 * Pay In Store payment method model
 */

class PaymentMethod extends \Magento\Payment\Model\Method\AbstractMethod
{

    /**
     * Constant variable
     */
    const METHOD_CODE = 'credova_payments';

    /**
     * Payment code
     *
     * @var string
     */
    protected $_code = self::METHOD_CODE;

    /**
     * Availability option
     *
     * @var boolean
     */
    protected $_isOffline = true;
}//end class
