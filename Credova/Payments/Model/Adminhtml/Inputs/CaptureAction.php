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
 * CaptureAction source model
 */
class CaptureAction extends \Magento\Eav\Model\Entity\Attribute\Source\AbstractSource
{
    /**
     * @var string[]
     */
    public function getAllOptions()
    {
        if ($this->_options === null) {
            $this->_options = [
                ['value' => 'authorize', 'label' => __('Authorize')],
                ['value' => 'authorize-capture', 'label' => __('Authorize & Capture')]
            ];
        }
        return $this->_options;
    }
}//end class
