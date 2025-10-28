<?php

namespace PublicSquare\Payments\Test\Unit\Controller\Customer;

use Magento\Customer\Model\Session;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Message\ManagerInterface;
use Magento\Vault\Api\Data\PaymentTokenFactoryInterface;
use Magento\Vault\Api\Data\PaymentTokenInterface;
use Magento\Vault\Api\PaymentTokenRepositoryInterface;
use PHPUnit\Framework\TestCase;
use PublicSquare\Payments\Controller\Customer\Card;
use PublicSquare\Payments\Helper\Config;
use PublicSquare\Payments\Logger\Logger;

class CardTest extends TestCase
{
    public function testExecuteCreatesAndSavesToken()
    {
        $resultFactory = $this->createMock(ResultFactory::class);
        $logger = $this->createMock(Logger::class);
        $request = $this->createMock(RequestInterface::class);
        $paymentTokenFactory = $this->createMock(PaymentTokenFactoryInterface::class);
        $paymentTokenRepository = $this->createMock(PaymentTokenRepositoryInterface::class);
        $psqConfig = $this->createMock(Config::class);
        $customerSession = $this->createMock(Session::class);
        $messageManager = $this->createMock(ManagerInterface::class);
        $encryptor = $this->createMock(EncryptorInterface::class);

        $paymentToken = $this->createMock(PaymentTokenInterface::class);
        $result = $this->createMock(ResultInterface::class);
        $customer = $this->getMockBuilder(\Magento\Customer\Model\Data\Customer::class)
            ->disableOriginalConstructor()
            ->getMock();

        // Set up request POST values
        $request->method('getPost')->willReturnMap([
            ['card_id', null, 'card123'],
            ['exp_year', null, 2025],
            ['exp_month', null, 12],
            ['details', null, '{"type":"visa"}'],
        ]);

        $customerSession->method('getCustomerId')->willReturn(42);
        $customerSession->method('getCustomer')->willReturn($customer);
        $customer->method('getWebsiteId')->willReturn(1);

        $paymentTokenFactory->method('create')->willReturn($paymentToken);

        $paymentToken->expects($this->once())->method('setCustomerId')->with(42);
        $paymentToken->expects($this->once())->method('setGatewayToken')->with('card123');
        $paymentToken->expects($this->once())->method('setPaymentMethodCode')->with(Config::CODE);
        $paymentToken->expects($this->once())->method('setIsVisible')->with(true);
        $paymentToken->expects($this->once())->method('setExpiresAt');
        $paymentToken->expects($this->once())->method('setWebsiteId')->with(1);
        $paymentToken->expects($this->once())->method('setTokenDetails')->with('{"type":"visa"}');

        $paymentToken->method('getTokenDetails')->willReturn('{"type":"visa"}');
        $paymentToken->method('getCustomerId')->willReturn(42);
        $paymentToken->method('getWebsiteId')->willReturn(1);
        $paymentToken->method('getGatewayToken')->willReturn('card123');
        $paymentToken->method('getExpiresAt')->willReturn(new \DateTime());

        $encryptor->method('hash')->willReturn('hashedvalue');
        $paymentToken->expects($this->once())->method('setPublicHash')->with('hashedvalue');

        $paymentTokenRepository->expects($this->once())->method('save')->with($paymentToken);

        $resultFactory->method('create')->with(ResultFactory::TYPE_REDIRECT)->willReturn($result);
        $result->expects($this->once())->method('setPath')->with('vault/cards/listaction');

        $controller = new Card(
            $resultFactory,
            $logger,
            $request,
            $paymentTokenFactory,
            $paymentTokenRepository,
            $psqConfig,
            $customerSession,
            $messageManager,
            $encryptor
        );

        $this->assertSame($result, $controller->execute());
    }
}
