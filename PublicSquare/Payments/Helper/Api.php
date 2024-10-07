<?php
/**
 * Api helper
 *
 * @category  PublicSquare
 * @package   PublicSquare_Payments
 * @author    PublicSquare <info@publicsquare.com>
 * @copyright 2024 PublicSquare
 * @license   http://opensource.org/licenses/osl-3.0.php (OSL 3.0)
 * @link      https://publicsquare.com/
 */

namespace PublicSquare\Payments\Helper;

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
