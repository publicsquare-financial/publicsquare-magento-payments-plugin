<?php

namespace Magento\Framework\Controller;

interface ResultFactory
{
    const TYPE_JSON = 'json';
    const TYPE_RAW = 'raw';
    const TYPE_REDIRECT = 'redirect';
    const TYPE_FORWARD = 'forward';
    const TYPE_LAYOUT = 'layout';
    const TYPE_PAGE = 'page';

    function create(string $type, array $arguments = []);

}
