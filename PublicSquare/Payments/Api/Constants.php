<?php
namespace PublicSquare\Payments\Api;

class Constants {
    public const REFUND_ID_KEY = 'psq_refund_id';
    public const SETTLEMENT_ID_KEY = 'psq_settlement_id';

    public const WEBHOOK_EVENT_SETTLEMENT_UPDATED = 'settlement:updated';
    public const WEBHOOK_EVENT_REFUND_UPDATED = 'refund:updated';

}
