<?php
/**
 * CheckoutFlow
 *
 * @category  Credova
 * @package   Credova_Payments
 * @author    Credova <info@credova.com>
 * @copyright 2024 Credova
 * @license   http://opensource.org/licenses/osl-3.0.php (OSL 3.0)
 * @link      https://credova.com/
 */

namespace Credova\Payments\Model\Adminhtml\Inputs;

/**
 * PreAuthorizationType source model
 */
class PreAuthorizationType extends \Magento\Eav\Model\Entity\Attribute\Source\AbstractSource
{
    /**
     * @var string[]
     */
    public function getAllOptions()
    {
        if ($this->_options === null) {
            $this->_options = [
                ['value' => 'live', 'label' => __('Live ($0.01 test transaction)')],
                ['value' => 'test', 'label' => __('Test (Card number validation only)')],
                ['value' => 0, 'label' => __('None (No validation performed)')]
            ];
        }
        return $this->_options;
    }
}//end class
