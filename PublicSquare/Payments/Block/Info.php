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
        if ($rawDetails = $this->getRawDetailsInfo()) {
            $data = [
                (string)__('Payment Status') => str_replace('_', ' ', (string)__($rawDetails['status'])),
                (string)__('Payment ID') => $rawDetails['id'],
                (string)__('Payment Details') => $this->getPaymentDetailsLink('Details'),
                (string)__('AVS Response') => 'Y',
                (string)__('CVV Response') => 'M',
            ];
            if ($fraudDecision = $this->getFraudDecision()) {
                $data[(string)__('Fraud Decision')] = (string)__($fraudDecision);
                if ($fraudRules = $this->getFraudRules()) {
                    foreach ($fraudRules as $rule) {
                        $data[(string)__('Rule failed') . ' - ' . $rule['rule_engine']] = $rule['rule_description'];
                    }
                }
                if ($fraudDecision !== 'accept') {
                    $data[(string)__('Fraud Review Link')] = $this->getPaymentDetailsLink($fraudDecision === 'review' ? 'Review' : 'Details');
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
        try {
            if ($this->getRawDetailsInfo()) {
                return $this->getRawDetailsInfo()['fraud_decision']['decision'];
            }
        } catch (\Exception $e) {}
        return null;
    }

    public function getFraudRules()
    {
        try {
            if ($this->getRawDetailsInfo()) {
                return $this->getRawDetailsInfo()['fraud_decision']['rules'];
            }
        } catch (\Exception $e) {}
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
