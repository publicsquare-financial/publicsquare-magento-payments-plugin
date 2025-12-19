<?php

namespace PublicSquare\Payments;

interface ICardInputCustomizationJSON
{
    public function getCardInputCustomizationJSON(): string|null;
}