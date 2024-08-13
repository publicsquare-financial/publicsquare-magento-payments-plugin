<?php
/**
 * Api helper
 *
 * @category  Credova
 * @package   Credova_Payments
 * @author    Credova <info@credova.com>
 * @copyright 2024 Credova
 * @license   http://opensource.org/licenses/osl-3.0.php (OSL 3.0)
 * @link      https://credova.com/
 */

namespace Credova\Payments\Helper;

use Magento\Framework\App\Helper\Context;

class Api extends \Magento\Framework\App\Helper\AbstractHelper
{
    protected $apiToken = null;

    /**
     * Api constructor.
     *
     * @param Context                                 $context
     */
    public function __construct(
        Context $context
    ) {
        parent::__construct($context);
    }//end __construct()
}//end class
