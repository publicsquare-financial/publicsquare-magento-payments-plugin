<?php

namespace Magento\Framework\App;


interface RequestInterface
{
 function getPost(string $key);
 function getActionName(): string;
 function getContent();
 function getHeader($header);
}