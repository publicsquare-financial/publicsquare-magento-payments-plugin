<?php

namespace PublicSquare\Payments\Gateway\Command;

use Magento\Payment\Gateway\CommandInterface;

class AuthorizeCaptureCommand implements CommandInterface
{
    public function execute(array $commandSubject)
    {
        dd($commandSubject);
    }
}