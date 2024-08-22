<?php
/**
 * Credova_Payments
 *
 * @category  Credova
 * @package   Credova_Payments
 * @author    Credova <info@credova.com>
 * @copyright 2024 Credova
 * @license   http://opensource.org/licenses/osl-3.0.php (OSL 3.0)
 * @link      https://credova.com/
 */

namespace Credova\Payments\Model;

use Magento\Payment\Model\Method\Adapter;
use Magento\Payment\Gateway\Command\CommandPoolInterface;
use Magento\Payment\Gateway\Config\ValueHandlerPoolInterface;
use Magento\Payment\Gateway\Data\PaymentDataObjectFactory;
use Magento\Payment\Gateway\Validator\ValidatorPoolInterface;
use Magento\Framework\Event\ManagerInterface;
use Credova\Payments\Helper\Config;

class PaymentMethod extends Adapter
{
    protected $config;

    public function __construct(
        ManagerInterface $eventManager,
        ValueHandlerPoolInterface $valueHandlerPool,
        PaymentDataObjectFactory $paymentDataObjectFactory,
        string $code,
        string $formBlockType,
        string $infoBlockType,
        Config $config,
        CommandPoolInterface $commandPool = null,
        ValidatorPoolInterface $validatorPool = null
    ) {
        parent::__construct(
            $eventManager,
            $valueHandlerPool,
            $paymentDataObjectFactory,
            $code,
            $formBlockType,
            $infoBlockType,
            $commandPool,
            $validatorPool
        );
        $this->config = $config;
    }

    public function isAvailable(\Magento\Quote\Api\Data\CartInterface $quote = null)
    {
        if ($quote && $quote->getBaseGrandTotal() < $this->config->getActive()) {
            return false;
        }
        return parent::isAvailable($quote);
    }

    public function canUseForCurrency($currencyCode)
    {
        return in_array($currencyCode, $this->config->getAllowedCurrencies());
    }

    public function authorize(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        $commandCode = $this->getAuthorizeCommandCode();
        $this->executeCommand($commandCode, ['payment' => $payment, 'amount' => $amount]);
        return $this;
    }

    public function capture(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        if ($this->config->getPreAuthorizationType() === 'authorize') {
            // If it's authorization only, we need to capture a previously authorized payment
            $this->executeCommand('capture', ['payment' => $payment, 'amount' => $amount]);
        } else {
            // If it's auth+capture, we use the same command as authorize
            $commandCode = $this->getAuthorizeCommandCode();
            $this->executeCommand($commandCode, ['payment' => $payment, 'amount' => $amount]);
        }
        return $this;
    }

    private function getAuthorizeCommandCode()
    {
        return $this->config->getPreAuthorizationType() ? 'authorize' : 'authorize_capture';
    }

    private function executeCommand($commandCode, array $arguments = [])
    {
        // $this->commandPool->get($commandCode)->execute($arguments);
    }
}