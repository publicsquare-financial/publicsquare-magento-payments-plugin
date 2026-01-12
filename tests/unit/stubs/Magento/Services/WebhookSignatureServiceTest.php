<?php

namespace Magento\Services;

use phpseclib3\Crypt\RSA;
use PHPUnit\Framework\TestCase;
use PublicSquare\Payments\Helper\Config;
use PublicSquare\Payments\Logger\Logger;
use PublicSquare\Payments\Services\WebhookSignatureService;

class WebhookSignatureServiceTest extends TestCase
{
    private Config $config;

    private Logger $logger;
    private WebhookSignatureService $webhookSignatureService;

    protected function setUp(): void
    {
        $this->config = $this->createMock(Config::class);
        $this->logger = $this->createMock(Logger::class);
        $this->logger->method('withName')->willReturn($this->logger);
        $this->webhookSignatureService = new WebhookSignatureService(
            logger: $this->logger,
            config: $this->config,
        );
    }

    public function testValidateSignature()
    {
        $pk = RSA::createKey(2048);
        $pub = $pk->getPublicKey();
        $pubKeyStr = (string)$pub;
        $this->config->method('getWebhookKey')->willReturn($pubKeyStr);

        $body = json_encode([
            'id' => 'event_1',
            'event_type' => 'test',
        ], JSON_THROW_ON_ERROR);
        $signature = $pk->sign($body);


        self::assertTrue($this->webhookSignatureService->verify(body: $body, signature:
            base64_encode($signature)));
    }

    public function testInValidateSignature()
    {
        $pk = RSA::createKey(2048);
        $pub = $pk->getPublicKey();
        $pubKeyStr = (string)$pub;
        $b64pub = base64_encode($pubKeyStr);


        $this->config->method('getWebhookKey')->willReturn($b64pub);


        $body = json_encode([
            'id' => 'event_1',
            'event_type' => 'test',
        ], JSON_THROW_ON_ERROR);
        $pk2 = RSA::createKey(2048);
        $signature = $pk2->sign($body);


        self::assertFalse($this->webhookSignatureService->verify(body: $body, signature: base64_encode($signature)));
    }

    public function testValidateSignature_withStaticKey()
    {
        $pk = RSA::loadPrivateKey(
            "-----BEGIN PRIVATE KEY-----
MIIEvAIBADANBgkqhkiG9w0BAQEFAASCBKYwggSiAgEAAoIBAQCpSQV1rPjJZZ3R
JX4wR8LDWlA25fbsTWu9HgQ8BlE9gNYExCQky/t9APpU/rRXPHhN9ii0FeLYdlbH
HTpqwpeYZWH82lKB7dGxCqdYRbAyABhoRYEH6cNaxLht5mR5FoHZaTsxWCseBYDd
/RlPZW9CbTAV5GCUMnPxiNaAhWyO7EWalFmcdq1lj/bvAxym/wiA760mTIH95B5u
rTYkhUHpvO+ymub77t3dpklt9iZfGi3P15ggaiTj68Usu3ObLfk8PYWyzjD/O1hL
KYItYnkrhiESuhoLnFa3qMCIZLX0i4F9s0lu3ENjtD5CdmrYhLchXmr0lNS+35bR
2enWG1fXAgMBAAECggEABA7XDaoW0KUZ8mCGtNuThKFOmPJMR//XHFJy4Yl5OxMy
jiyxfRxSq/1xAsaURh8R9zR2Z1K6/Fth2yYNN2/wuFt9zNTi83Bi/W528nvBLIGq
FB3OaQUmhK+AiEnkkK6EcFTAcX9ekTqqiye9CpUw5JO/elbeJc9LzjdshOot3wMq
6i65qYAOtA0EBK6pQ6KtoTZXJ5v6eRDnSC9tN7Ld5YRW6RkNAyvOZMn17pq5Wnu1
rZM6eysVNgZTatkqTmrPejteVAnBqb8aJtRPz/frrSdi94nopusga9VveAF3HS7S
AKjCuID1YgeCihKN48jCF0FR1nrnEr3I/UH9zHrjuQKBgQDf4U7DzARHI6xMd/nS
0UW9apKCTnf8cxTZGo190KR6knqJWuMOeGeSfQCt03jyVYb5Xhwc0HJq7gZ5NP70
fg7l6EUMV7RRfwJjB1JM9v9hN6VsE0T4gSATzV0tQa+ZzONoY0BgWx8HEqCr62uo
ewpNWj4YmewUtgL/qF8aIJrICwKBgQDBkowrXmzTaymPyjHfYLuIDMEpOMQMyVKN
/c5XL9oAJiwBZzR8vBpZdR8Nvefx2DCXeT5S/4oo6hNXLuHzXh7uATcer/xtWfd3
o5TxtDsQm4vzlY7Yupe+xC/8op++Wdtl8HIIpaKHmmxJ5MvdhfKxeagL/aeuqBci
evWrSyTy5QKBgGU3GoIAsYpsAVCNCUAbZrks/lG1Ih/a83j3vTI9aq8TnByPH4oC
O2kJ5I2xxsNgkWYZ+wG355KaTAjuQbnNZ/TfHqBm4lnZ3v1gaP/sxyZvnvUOfScQ
Ua1CMjbstHQHImSmQouNhqiO7l7rTz/baJvyCZLbu9TdONvWhjBsvy1jAoGAATwQ
DM7DXu3WDAa8HrKdP5blPIASMAqwrhsqT8AMYefca/3ehdUlTeDDW/EUI6S/Wpf5
X8oldXFYwjuYUVcOV3JGibmRoJjsTSUL9Ca0Ibz8PYd8q8E06pCRxci7wBkOny3T
bF6yFK9VdXsyGa2bCKq/+aOaiDCmRSAHpRoLmEkCgYASUJ4f6KprZeRykp2No19R
8qcpjO0EeCBy7TR/ZGUHXRAIRcgtUobZ375YHrbxY8RIkUNwIRTg45QHIP5U9ybL
jzDhevDZKouX44G8G60aKkztTvLn67WMnN7mji5QGpdRXGckTT8wp4sQPcHNuhdC
hZLHw7WW3u2FlwyHREarJA==
-----END PRIVATE KEY-----",
        );


        $pubKeyStr = "-----BEGIN PUBLIC KEY-----
MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAqUkFdaz4yWWd0SV+MEfC
w1pQNuX27E1rvR4EPAZRPYDWBMQkJMv7fQD6VP60Vzx4TfYotBXi2HZWxx06asKX
mGVh/NpSge3RsQqnWEWwMgAYaEWBB+nDWsS4beZkeRaB2Wk7MVgrHgWA3f0ZT2Vv
Qm0wFeRglDJz8YjWgIVsjuxFmpRZnHatZY/27wMcpv8IgO+tJkyB/eQebq02JIVB
6bzvsprm++7d3aZJbfYmXxotz9eYIGok4+vFLLtzmy35PD2Fss4w/ztYSymCLWJ5
K4YhEroaC5xWt6jAiGS19IuBfbNJbtxDY7Q+QnZq2IS3IV5q9JTUvt+W0dnp1htX
1wIDAQAB
-----END PUBLIC KEY-----";
        $this->config->method('getWebhookKey')->willReturn($pubKeyStr);

        $body = json_encode([
            'id' => 'event_1',
            'event_type' => 'test',
        ], JSON_THROW_ON_ERROR);
        $pk = $pk->withPadding(RSA::SIGNATURE_PKCS1)->withHash('sha256');
        $signature = base64_encode($pk->sign($body));
        echo "Signature: " . $signature . "\n";


        self::assertTrue($this->webhookSignatureService->verify(body: $body, signature: $signature));
    }
}
