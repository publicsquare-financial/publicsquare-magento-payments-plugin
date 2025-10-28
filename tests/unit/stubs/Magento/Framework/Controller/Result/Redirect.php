<?php

namespace Magento\Framework\Controller\Result;


interface Redirect
{

    function setPath($path, array $params = []): Redirect;

}