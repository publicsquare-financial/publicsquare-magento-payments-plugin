<?php

namespace PublicSquare\Payments\Model;

interface ICardInputCustomizationJSON
{
    public function getCardInputCustomizationJSON(): string|null;
}