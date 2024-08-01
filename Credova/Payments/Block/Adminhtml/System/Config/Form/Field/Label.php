<?php

namespace Credova\Payments\Block\Adminhtml\System\Config\Form\Field;

use Magento\Framework\Data\Form\Element\AbstractElement;
use Credova\Payments\Model\ModuleInfoProvider;

class Label extends \Magento\Config\Block\System\Config\Form\Field
{

    /**
     * @var ModuleInfoProvider
     */
    private $moduleInfoProvider;
    
  

    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        ModuleInfoProvider $moduleInfoProvider
    ) {
        $this->moduleInfoProvider = $moduleInfoProvider;
        parent::__construct($context);
    }

    protected function _getElementHtml(AbstractElement $element)
    {
        $version = $this->getModuleVersion();
        return $version;
    }


    public function getModuleVersion()
    {
        $moduleInfo = $this->moduleInfoProvider->getModuleInfo('Credova_Financial');

        return $moduleInfo['version'];
    }
}
