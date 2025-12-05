<?php

namespace PublicSquare\Payments\Model\Adminhtml\Source;

use Magento\Framework\Data\OptionSourceInterface;

class CardFormLayout implements OptionSourceInterface
{
    public function toOptionArray()
    {
        return [
            ['value' => 'single', 'label' => __('Single Input')],
            ['value' => 'split-a', 'label' => __('Separate Inputs')],
        ];
    }
}
