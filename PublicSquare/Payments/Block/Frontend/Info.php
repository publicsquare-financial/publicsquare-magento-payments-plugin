<?php
/**
 * Copyright © PublicSquare, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace PublicSquare\Payments\Block\Frontend;

use Magento\Payment\Block\Info\Cc;

class Info extends Cc
{
    /**
     * Prepare credit card related payment info
     *
     * @param \Magento\Framework\DataObject|array $transport
     * @return \Magento\Framework\DataObject
     */
    protected function _prepareSpecificInformation($transport = null)
    {
        if (null !== $this->_paymentSpecificInformation) {
            return $this->_paymentSpecificInformation;
        }
        return parent::_prepareSpecificInformation($transport);
    }
}
