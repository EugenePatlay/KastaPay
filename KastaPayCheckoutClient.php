<?php

declare(strict_types=1);

namespace Parfums\SaleBundle\Service\Payment\KastaPay;

use GuzzleHttp\TransferStats;
use Parfums\SaleBundle\Entity\Order;
use Parfums\SaleBundle\Entity\OrderConsistence;
use Parfums\SaleBundle\Request\KastaPayServiceRequest;
use Parfums\UtilsBundle\Utils\Util;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Serializer\Encoder\JsonEncoder;

use function sprintf;

final class KastaPayCheckoutClient extends KastaPayClient
{
    private const URL_CHECKOUT = '/pay/compact?behavior=frame&build=42';
    private const TYPE_TRANSACTION = 'AUTH';
    private const TYPE_TRANSACTION_SECURE_TYPE = 'AUTO';

    /**
     * @var Util
     */
    private $util;

    /**
     * @param Util $util
     */
    public function setUtil(Util $util)
    {
        $this->util = $util;
    }

    /**
     * @param string $url
     * @param array  $payload
     *
     * @return string
     */
    private function makeRequest(string $url, array $payload): string
    {
        $uri = '';
        $this->logger->info('Checkout Request: ' . $this->jsonEncoder->encode($payload, JsonEncoder::FORMAT));
        $data = $this->client->post(
            $url,
            [
                'form_params' => $payload,
                'on_stats' => function (TransferStats $stats) use (&$uri) {
                    $uri = $stats->getEffectiveUri()->__toString();
                },
            ]
        );
        $this->logger->info('Checkout Response: ' . $uri);
        if ($this->client->getConfig('base_uri') . $url == $uri) {//если ошибка, то останемся на той же странице
            $this->logger->error($data->getBody()->getContents());
        }

        return $uri;
    }

    /**
     * @param Order $order
     * @param string $lang
     *
     * @return string
     */
    public function createPayment(Order $order, string $lang = 'ua'): string
    {
        $req = [
            'language' => $lang,
            'merchantAccount' => $this->merchant,
            'merchantDomainName' => $this->domain,
            'merchantTransactionType' => self::TYPE_TRANSACTION,
            'merchantTransactionSecureType' => self::TYPE_TRANSACTION_SECURE_TYPE,
            'amount' => (int)$order->getAmountToPay(),
            'clientPhone' => $order->getPhone(),
            'clientFirstName' => $order->getFirstname(),
            'clientLastName' => $order->getLastname(),
            'clientEmail' => $order->getEmail(),
            'currency' => 'UAH',
            'orderDate' => time(),
            'orderReference' => $order->encodeNumberForPayment(),
            'serviceUrl' => $this->router->generate('kastapay_payment_service', [], RouterInterface::ABSOLUTE_URL),
            'returnUrl' => $this->router->generate(
                'order_finish',
                ['orderNumber' => $order->getNumber()],
                RouterInterface::ABSOLUTE_URL
            ),
        ];
        /** @var OrderConsistence $consistence */
        foreach ($order->getOrderConsistence() as $consistence) {
            $req['productCount'][] = $consistence->getQuantity();
            $req['productPrice'][] = (int)$consistence->getPriceWithDiscount();
            $req['productName'][] = sprintf(
                '%s %s',
                $this->util->getText($consistence->getSKU()->getProduct(), 'name'),
                $this->util->getText($consistence->getSKU(), 'name')
            );
        }
        $this->singPaymentRequest($req);

        return $this->makeRequest(self::URL_CHECKOUT, $req);
    }

    /**
     * @param array $request
     */
    private function singPaymentRequest(array &$request): void
    {
        $arrayToSing = [
            $request['merchantAccount'],
            $request['merchantDomainName'],
            $request['orderReference'],
            $request['orderDate'],
            $request['amount'],
            $request['currency'],
        ];
        $arrayToSing = array_merge(
            $arrayToSing,
            $request['productName'],
            $request['productCount'],
            $request['productPrice']
        );
        $request['merchantSignature'] = $this->generateSign($arrayToSing);
    }

    /**
     * @param KastaPayServiceRequest $request
     *
     * @return bool
     */
    public function verifyServiceSign(KastaPayServiceRequest $request): bool
    {
        $arrayToSing = [
            $request->merchantAccount,
            $request->orderReference,
            $request->amount,
            $request->currency,
            $request->authCode,
            $request->cardPan,
            $request->transactionStatus,
            $request->reasonCode,
        ];

        return $request->merchantSignature == $this->generateSign($arrayToSing);
    }

    /**
     * @param KastaPayServiceRequest $request
     *
     * @return array
     */
    public function serviceResponseArray(KastaPayServiceRequest $request): array
    {
        $time = time();
        $arrayToSing = [$request->orderReference, $request->transactionStatus, $time];

        return [
            'order' => $request->orderReference,
            'status' => $request->transactionStatus,
            'time' => $time,
            'signature' => $this->generateSign($arrayToSing),
        ];
    }
}
