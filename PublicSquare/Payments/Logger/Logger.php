<?php
namespace PublicSquare\Payments\Logger;

use DateTimeZone;

class Logger extends \Monolog\Logger
{
   public function __construct(string $name = 'PSQ', array $handlers = [], array $processors = [], ?DateTimeZone $timezone = null)
   {
       parent::__construct($name, $handlers, $processors, $timezone);
   }

}
