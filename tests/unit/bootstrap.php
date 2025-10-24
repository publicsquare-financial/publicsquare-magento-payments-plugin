<?php
require_once __DIR__ . '/../../vendor/autoload.php';

// Create essential Magento classes for testing
// This is the most practical approach - minimal but complete

// Helper classes
if (!class_exists('Magento\Framework\App\Helper\Context')) {
    eval('namespace Magento\\Framework\\App\\Helper; class Context {}');
}

if (!class_exists('Magento\Framework\App\Helper\AbstractHelper')) {
    eval('namespace Magento\\Framework\\App\\Helper; abstract class AbstractHelper { protected $scopeConfig; public function __construct(Context $context) {} }');
}

// ObjectManager (simple)
if (!class_exists('Magento\Framework\App\ObjectManager')) {
    eval('namespace Magento\\Framework\\App; class ObjectManager { public static function getInstance() { return new self(); } public function get($className) { return new \\stdClass(); } }');
}

// Serializer
if (!class_exists('Magento\Framework\Serialize\Serializer\Json')) {
    eval('namespace Magento\\Framework\\Serialize\\Serializer; class Json { public function serialize($data) { return json_encode($data); } public function unserialize($string) { return json_decode($string, true); } }');
}

// Scope interface
if (!interface_exists('Magento\Store\Model\ScopeInterface')) {
    eval('namespace Magento\\Store\\Model; interface ScopeInterface { const SCOPE_STORE = "store"; const SCOPE_WEBSITE = "website"; const SCOPE_DEFAULT = "default"; }');
}
