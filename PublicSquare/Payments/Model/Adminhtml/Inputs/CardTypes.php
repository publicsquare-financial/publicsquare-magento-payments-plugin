<?php
/**
 * CheckoutFlow
 *
 * @category  PublicSquare
 * @package   PublicSquare_Payments
 * @author    PublicSquare <info@publicsquare.com>
 * @copyright 2024 PublicSquare
 * @license   http://opensource.org/licenses/osl-3.0.php (OSL 3.0)
 * @link      https://publicsquare.com/
 */

namespace PublicSquare\Payments\Model\Adminhtml\Inputs;

/**
 * CardTypes source model
 */
class CardTypes extends \Magento\Eav\Model\Entity\Attribute\Source\AbstractSource
{
    /**
     * @var string[]
     */
    public function getAllOptions()
    {
        if ($this->_options === null) {
            $this->_options = [
                ['value' => 'amex', 'label' => __('AMEX')],
                ['value' => 'visa', 'label' => __('VISA')],
                ['value' => 'mastercard', 'label' => __('Mastercard')],
                ['value' => 'discover', 'label' => __('Discover')]
            ];
        }
        return $this->_options;
    }
}//end class
