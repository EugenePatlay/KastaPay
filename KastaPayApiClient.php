<?php

declare(strict_types=1);

namespace Parfums\SaleBundle\Service\Payment\KastaPay;

use Parfums\SaleBundle\Entity\Order;
use Parfums\SaleBundle\Request\KastaPay1CSettleRequest;
use Parfums\SaleBundle\Response\KastaPayRefundResponse;
use Parfums\SaleBundle\Response\KastaPaySettleResponse;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\SerializerInterface;

final class KastaPayApiClient extends KastaPayClient
{
    private const API_VERSION = 1;
    private const URL_V1 = '/v1';
    private const URL_API = '/api';
    private const TYPE_TRANSACTION_SETTLE = 'SETTLE';
    private const TYPE_TRANSACTION_REFUND = 'REFUND';
    private const TYPE_TRANSACTION_PAYOUTS = 'payouts';
    private const TYPE_TRANSACTION_PAYOUT = 'payout';

    /** @var SerializerInterface */
    private $serializer;

    /**
     * @param SerializerInterface $serializer
     */
    public function setSerializer(SerializerInterface $serializer)
    {
        $this->serializer = $serializer;
    }

    /**
     * @param KastaPay1CSettleRequest $request
     * @param Order                   $order
     *
     * @return KastaPaySettleResponse
     */
    public function createSettle(KastaPay1CSettleRequest $request, Order $order): KastaPaySettleResponse
    {
        $orderNumber = $this->checkAndReturnRequest1cOrder($request, $order);
        $req = $this->createTransactionReq($request, $orderNumber);
        $req['transactionType'] = self::TYPE_TRANSACTION_SETTLE;
        $resp = $this->makeRequest(self::URL_API, $req);
        /** @var KastaPaySettleResponse $dto */
        $dto = $this->serializer->deserialize(
            $resp,
            KastaPaySettleResponse::class,
            JsonEncoder::FORMAT,
            [ObjectNormalizer::DISABLE_TYPE_ENFORCEMENT => true]
        );

        return $dto;
    }

    /**
     * @param KastaPay1CSettleRequest $request
     * @param Order                   $order
     *
     * @return KastaPayRefundResponse
     */
    public function createRefund(KastaPay1CSettleRequest $request, Order $order): KastaPayRefundResponse
    {
        $orderNumber = $this->checkAndReturnRequest1cOrder($request, $order);
        $req = $this->createTransactionReq($request, $orderNumber);
        $req['transactionType'] = self::TYPE_TRANSACTION_REFUND;
        $req['comment'] = $request->comment;
        $resp = $this->makeRequest(self::URL_API, $req);
        /** @var KastaPayRefundResponse $dto */
        $dto = $this->serializer->deserialize(
            $resp,
            KastaPayRefundResponse::class,
            JsonEncoder::FORMAT,
            [ObjectNormalizer::DISABLE_TYPE_ENFORCEMENT => true]
        );

        return $dto;
    }

    /**
     * @param KastaPay1CSettleRequest $request
     * @param string                  $orderNumber
     *
     * @return array
     */
    private function createTransactionReq(KastaPay1CSettleRequest $request, string $orderNumber): array
    {
        $req = [
            'apiVersion' => self::API_VERSION,
            'merchantAccount' => $this->merchant,
            'orderReference' => $orderNumber,
            'amount' => $request->amount,
            'currency' => 'UAH',
            'merchantSignature' => $this->generateSign([$this->merchant, $orderNumber, $request->amount, 'UAH']),
        ];
        foreach ($request->transactions as $transaction) {
            $req['partnerCode'][] = $transaction['smch_id'];
            $req['partnerOrder'][] = $orderNumber;
            $req['partnerPrice'][] = $transaction['amount'];
        }

        return $req;
    }

    /**
     * @param string $date
     *
     * @return array
     */
    public function getPayouts(string $date): array
    {
        $resp = $this->makeRequest(
            self::URL_V1,
            [
                'requestType' => self::TYPE_TRANSACTION_PAYOUTS,
                'date' => $date,
                'merchantAccount' => $this->merchant,
                'merchantSignature' => $this->generateSign([$this->merchant, self::TYPE_TRANSACTION_PAYOUTS, $date]),
            ]
        );

        return $this->jsonEncoder->decode($resp, JsonEncoder::FORMAT);
    }

    /**
     * @param string $id
     *
     * @return array
     */
    public function getPayout(string $id): array
    {
        $resp = $this->makeRequest(
            self::URL_V1,
            [
                'requestType' => self::TYPE_TRANSACTION_PAYOUT,
                'id' => $id,
                'merchantAccount' => $this->merchant,
                'merchantSignature' => $this->generateSign([$this->merchant, self::TYPE_TRANSACTION_PAYOUTS, $id]),
            ]
        );

        return $this->jsonEncoder->decode($resp, JsonEncoder::FORMAT);
    }

    /**
     * @param string $url
     * @param array  $payload
     *
     * @return string
     */
    private function makeRequest(string $url, array $payload): string
    {
        $this->logger->info('API Request: ' . $this->jsonEncoder->encode($payload, JsonEncoder::FORMAT));
        /** @var ResponseInterface $resp */
        $resp = $this->client->post($url, ['json' => $payload]);
        $resp = $resp->getBody()->getContents();
        $this->logger->info('API Response: ' . $resp);

        return $resp;
    }

    /**
     * @param KastaPay1CSettleRequest $request
     * @param Order                   $order
     *
     * @return string
     */
    private function checkAndReturnRequest1cOrder(KastaPay1CSettleRequest $request, Order $order): string
    {
        if ($request->amount <= 0) {
            throw new BadRequestHttpException('Incorrect amount');
        }

        if (empty($payment = $order->getPaymentsData())) {
            throw new BadRequestHttpException('Empty payment data');
        }

        if (empty($payment->getPaymentsData()['orderReference'])) {
            throw new BadRequestHttpException('Empty orderReference payment data');
        }

        return $payment->getPaymentsData()['orderReference'];
    }
}
