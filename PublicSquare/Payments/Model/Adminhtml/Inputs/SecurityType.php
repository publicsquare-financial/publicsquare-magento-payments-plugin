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
 * SecurityType source model
 */
class SecurityType extends \Magento\Eav\Model\Entity\Attribute\Source\AbstractSource
{
    /**
     * @var string[]
     */
    public function getAllOptions()
    {
        if ($this->_options === null) {
            $this->_options = [
                ['value' => 'pci-saq-a', 'label' => __('Hosted iFrame - PCI SAQ A')]
            ];
        }
        return $this->_options;
    }
}//end class
