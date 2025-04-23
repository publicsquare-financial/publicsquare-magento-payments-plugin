<?php

declare(strict_types=1);

namespace PublicSquare\Payments\Model\Webhooks;

interface RequestBodyInterface extends \Magento\Framework\Api\ExtensibleDataInterface
{
  const ID = "id";
  const EVENT = "event";
  const DATA = "data";

  /**
   * 
   */
  public function getId(): string;

  /**
   * 
   */
  public function getEvent(): string;

  /**
   * 
   */
  public function getData(): string;
}
