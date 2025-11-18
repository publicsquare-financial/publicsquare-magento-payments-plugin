<?php

namespace PublicSquare\Payments\Test\Unit\Block\Frontend;

use PHPUnit\Framework\TestCase;

class FormTest extends TestCase
{
    public function testTemplateContainsCardElementDiv(): void
    {
        $root = dirname(__DIR__, 4);
        $templatePath = $root . '/PublicSquare/Payments/view/frontend/templates/form/multishipping-cc.phtml';

        $this->assertFileExists($templatePath, 'Multishipping template file should exist');

        $contents = file_get_contents($templatePath);
        $this->assertIsString($contents);

        // Ensure the container that hosts the card element is present
        $this->assertStringContainsString('id="publicsquare-elements-form"', $contents);

        // Hidden inputs used for submission
        $this->assertStringContainsString('name="payment[additional_data][cardId]"', $contents);
        $this->assertStringContainsString('name="payment[additional_data][idempotencyKey]"', $contents);
    }

    public function testFrontendFormDeclaresMultishippingTemplateInSource(): void
    {
        // Avoid loading Magento classes; assert via source contents
        $root = dirname(__DIR__, 4);
        $blockPath = $root . '/PublicSquare/Payments/Block/Frontend/Form.php';
        $this->assertFileExists($blockPath);
        $src = file_get_contents($blockPath);
        $this->assertIsString($src);
        $this->assertStringContainsString("protected \$_template = 'PublicSquare_Payments::form/multishipping-cc.phtml';", $src);
    }

    public function testConfigEnablesMultishipping(): void
    {
        $root = dirname(__DIR__, 4);
        $configPath = $root . '/PublicSquare/Payments/etc/config.xml';

        $this->assertFileExists($configPath, 'Module config.xml should exist');
        $xml = file_get_contents($configPath);
        $this->assertIsString($xml);

        $this->assertStringContainsString('<can_use_for_multishipping>1</can_use_for_multishipping>', $xml);
    }
}


