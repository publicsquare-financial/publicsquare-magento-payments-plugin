<?php

namespace Magento\Framework\App;

interface CsrfAwareActionInterface
{
    public function createCsrfValidationException(RequestInterface $request): ?\Magento\Framework\App\Request\InvalidRequestException;
    public function validateForCsrf(RequestInterface $request): ?bool;
}