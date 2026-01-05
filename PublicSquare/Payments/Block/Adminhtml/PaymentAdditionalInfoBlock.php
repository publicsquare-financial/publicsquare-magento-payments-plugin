<?php

namespace PublicSquare\Payments\Block\Adminhtml;

class PaymentAdditionalInfoBlock extends
    \Magento\Sales\Block\Adminhtml\Order\View\Tab\Info
{
    private array $whitelist = [
        'psq_refund_id',
        'psq_settlement_id',
    ];

    private array $labels = [
        'psq_refund_id' => 'Refund ID',
        'psq_settlement_id' => 'Settlement ID',
    ];

    function paymentAdditionalInfo(): array
    {
        return array_filter(
            ($this->getOrder()->getPayment() ? $this->getOrder()->getPayment()->getAdditionalInformation() : []) ?? [],
            function ($value, $key) {
                return in_array($key, $this->whitelist, false);
            },
            ARRAY_FILTER_USE_BOTH
        );
    }

    function isVisible(): bool
    {
        return count($this->paymentAdditionalInfo()) > 0;
    }
}