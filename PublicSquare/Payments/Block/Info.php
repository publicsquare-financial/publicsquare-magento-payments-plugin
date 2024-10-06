<?php
/**
 * Copyright © PublicSquare, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace PublicSquare\Payments\Block;

use Magento\Payment\Block\Info\Cc;

class Info extends Cc
{
    protected $_template = 'PublicSquare_Payments::info/default.phtml';
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
        $transport = parent::_prepareSpecificInformation($transport);
        $data = [];
        if ($this->getRawDetailsInfo()) {
            $data = [
                (string)__('Payment Status') => (string)__($this->getRawDetailsInfo()['status'])
            ];
            $fraudDecision = $this->getFraudDecision();
            if ($fraudDecision) {
                $data[(string)__('Fraud Decision')] = (string)__($fraudDecision);
                if ($fraudDecision !== 'accept') {
                    $data[(string)__('Fraud Review Link')] = $this->getPaymentDetailsLink($fraudDecision === 'review' ? 'Review' : 'Details');
                } else {
                    $data[(string)__('Payment Details')] = $this->getPaymentDetailsLink('Details');
                }
            }
        }
        return $transport->setData(array_merge($transport->getData(), $data));
    }

    public function getRawDetailsInfo()
    {
        return $this->getInfo()->getAdditionalInformation('raw_details_info');
    }

    public function getFraudDecision()
    {
        if ($this->getRawDetailsInfo()) {
            return $this->getRawDetailsInfo()['fraud_decision']['decision'];
        }
        return null;
    }

    public function getPaymentDetailsLink(string $label)
    {
        if ($this->getRawDetailsInfo()) {
            return '<a href="https://portal.publicsquare.com/transactions/payments/'.$this->getInfo()->getAdditionalInformation('raw_details_info')['id'].'" target="_blank">'.(string)__($label).' ↗</a>';
        }
        return null;
    }
}
