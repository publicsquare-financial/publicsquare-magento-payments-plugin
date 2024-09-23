<?php
/**
 * PublicSquare_Payments
 *
 * @category  PublicSquare
 * @package   PublicSquare_Payments
 * @author    PublicSquare <info@publicsquare.com>
 * @copyright 2024 PublicSquare
 * @license   http://opensource.org/licenses/osl-3.0.php (OSL 3.0)
 * @link      https://publicsquare.com/
 */

namespace PublicSquare\Payments\Model;

use Magento\Payment\Model\Method\Adapter;
use Magento\Payment\Gateway\Command\CommandPoolInterface;
use Magento\Payment\Gateway\Config\ValueHandlerPoolInterface;
use Magento\Payment\Gateway\Data\PaymentDataObjectFactory;
use Magento\Payment\Gateway\Validator\ValidatorPoolInterface;
use Magento\Framework\Event\ManagerInterface;
use PublicSquare\Payments\Helper\Config;
use Magento\Vault\Model\VaultPaymentInterface;

class PaymentMethod extends Adapter
{
    protected $config;
    protected $commandPool;
    protected $vault;

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
        // VaultPaymentInterface $vault
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
        $this->commandPool = $commandPool;
        // $this->vault = $vault;
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

    // public function authorize(\Magento\Payment\Model\InfoInterface $payment, $amount)
    // {
    //     $this->executeCommand('authorize', ['payment' => $payment, 'amount' => $amount]);
    //     return $this;
    // }

    // public function capture(\Magento\Payment\Model\InfoInterface $payment, $amount)
    // {
    //     // if (in_array($this->config->getPreAuthorizationType(), array('authorize', 'authorize_capture'))) {
    //     //     // If it's authorization only, we need to capture a previously authorized payment
    //     //     $this->executeCommand('authorize', ['payment' => $payment, 'amount' => $amount]);
    //     // } else {
    //     //     // If it's auth+capture, we use the same command as authorize
    //     //     $this->executeCommand('authorize', ['payment' => $payment, 'amount' => $amount]);
    //     // }
    //     $this->executeCommand('capture', ['payment' => $payment, 'amount' => $amount]);
    //     return $this;
    // }

    private function getAuthorizeCommandCode()
    {
        return $this->config->getPreAuthorizationType() === 'authorize' ? 'authorize' : 'authorize';
    }

    // private function executeCommand($commandCode, array $arguments = [])
    // {
    //     $this->commandPool->get($commandCode)->execute($arguments);
    // }
}