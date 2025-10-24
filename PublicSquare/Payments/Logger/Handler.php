<?php
namespace PublicSquare\Payments\Logger;

use Monolog\Level;
use Monolog\Logger;

class Handler extends \Magento\Framework\Logger\Handler\Base
{
    /**
     * Logging level
     *
     * @var int
     */
    protected $loggerType = Level::Debug;

    /**
     * File name
     *
     * @var string
     */
    protected $fileName = '/var/log/publicsquare-payments-debug.log';
}
