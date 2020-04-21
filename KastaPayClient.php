<?php

declare(strict_types=1);

namespace Parfums\SaleBundle\Service\Payment\KastaPay;

use GuzzleHttp\ClientInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Serializer\Encoder\JsonEncoder;

abstract class KastaPayClient
{
    public const RESPONSE_CODE_SUCCESS = 1100;

    /** @var string */
    protected $sign;
    /** @var string */
    protected $merchant;
    /** @var ClientInterface */
    protected $client;
    /** @var string */
    protected $domain;
    /** @var LoggerInterface */
    protected $logger;
    /** @var RouterInterface */
    protected $router;
    /** @var JsonEncoder */
    protected $jsonEncoder;

    public function __construct(
        string $sign,
        string $merchant,
        string $domain,
        LoggerInterface $logger,
        ClientInterface $client,
        RouterInterface $router
    ) {
        $this->merchant = $merchant;
        $this->sign = $sign;
        $this->logger = $logger;
        $this->domain = $domain;
        $this->client = $client;
        $this->router = $router;
        $this->jsonEncoder = new JsonEncoder();
    }

    /**
     * @param array $arrayToSing
     *
     * @return string
     */
    public function generateSign(array $arrayToSing): string
    {
        return hash_hmac('md5', implode(';', $arrayToSing), $this->sign);
    }
}
