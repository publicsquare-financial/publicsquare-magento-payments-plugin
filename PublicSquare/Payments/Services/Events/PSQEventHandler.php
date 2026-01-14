<?php

namespace PublicSquare\Payments\Services\Events;

interface PSQEventHandler
{
    public function handleEvent(array $event): void;
}