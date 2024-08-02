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
 * ThreeDSecureAuthentication source model
 */
class ThreeDSecureAuthentication extends \Magento\Eav\Model\Entity\Attribute\Source\AbstractSource
{
    /**
     * @var string[]
     */
    public function getAllOptions()
    {
        if ($this->_options === null) {
            $this->_options = [
                ['value' => 0, 'label' => __('No')],
                ['value' => 'when-required', 'label' => __('When required')],
                ['value' => 'always', 'label' => __('Always')]
            ];
        }
        return $this->_options;
    }
}//end class
