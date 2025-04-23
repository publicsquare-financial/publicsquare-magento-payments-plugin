<?php

namespace PublicSquare\Payments\Api\Webhooks;

enum WebhookEventType: string
{
  case PAYMENT_UPDATE = "payment:update";
  case REFUND_UPDATE = "refund:update";
}

enum PaymentStatus: string
{
  case REQUIRES_CAPTURE = "requires_capture";
  case SUCCEEDED = "succeeded";
  case PENDING = "pending";
  case REJECTED = "rejected";
  case DECLINED = "declined";
  case CANCELLED = "cancelled";
  case ERROR = "error";
}

enum RefundStatus: string
{
  case SUCCEEDED = "succeeded";
  case PENDING = "pending";
  case REJECTED = "rejected";
  case DECLINED = "declined";
  case CANCELLED = "cancelled";
  case ERROR = "error";
}

interface WebhookInterface
{
  /**
   * POST for publicsquare/webhooks api
   * @param string $id
   * @param string $event_type
   * @param string $entity_type
   * @param string $entity_id
   * @param mixed $entity
   * @return string
   */
  public function execute(string $id, string $event_type, string $entity_type, string $entity_id, mixed $entity);
}
