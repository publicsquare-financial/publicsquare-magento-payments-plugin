<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace PublicSquare\Payments\Model\Adminhtml\Source;

class CcType extends \Magento\Payment\Model\Source\Cctype
{
    const ALLOWED_TYPES = ['visa', 'mastercard', 'american_express', 'discover', 'jcb', 'diners'];
    /**
     * Allowed credit card types
     *
     * @return string[]
     */
    public function getAllowedTypes()
    {
        return self::ALLOWED_TYPES;
    }

    /**
     * Returns credit cards types
     *
     * @return array
     */
    public function getCcTypeLabelMap()
    {
        return $this->_paymentConfig->getCcTypes();
    }

    /**
     * @inheritdoc
     */
    public function toOptionArray()
    {
        $allowed = $this->getAllowedTypes();
        $options = [];

        foreach ($this->getCcTypeLabelMap() as $code => $name) {
            if (in_array($code, $allowed)) {
                $options[] = ['value' => $code, 'label' => $name];
            }
        }

        return $options;
    }
}
