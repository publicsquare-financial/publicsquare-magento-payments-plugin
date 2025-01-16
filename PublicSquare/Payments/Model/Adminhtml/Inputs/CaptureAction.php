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
                ['value' => 'authorize_capture', 'label' => __('Authorize & Capture')]
            ];
        }
        return $this->_options;
    }
}//end class
