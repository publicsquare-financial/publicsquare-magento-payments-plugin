<?php

namespace Magento\Framework\App;


interface RequestInterface
{
 function getPost(string $key);
 function getActionName(): string;
}