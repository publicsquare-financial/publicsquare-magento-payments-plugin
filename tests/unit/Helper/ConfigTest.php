<?php

namespace PublicSquare\Payments\Test\Unit\Helper;

use PHPUnit\Framework\TestCase;
use PublicSquare\Payments\Helper\Config;

class ConfigTest extends TestCase
{
    public function testGetAllowedCurrenciesReturnsUSD(): void
    {
        $contextMock = $this->createMock(\Magento\Framework\App\Helper\Context::class);
        
        $config = new Config($contextMock);
        $result = $config->getAllowedCurrencies();
        
        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertEquals(['USD'], $result);
        $this->assertContains('USD', $result);
    }
    
    public function testGetUriiReturnsCorrectBaseUrl(): void
    {
        $contextMock = $this->createMock(\Magento\Framework\App\Helper\Context::class);
        
        $config = new Config($contextMock);
        $result = $config->getUrii();
        
        $this->assertIsString($result);
        $this->assertEquals('https://api.publicsquare.com', $result);
    }
}
