<?php

namespace Parfums\SaleBundle\Request;

use Parfums\Api\Service\Request\DeserializableDTOInterface;

class KastaPayServiceRequest implements DeserializableDTOInterface
{
    /** @var string */
    public $merchantAccount;
    /** @var string */
    public $orderReference;
    /** @var string */
    public $merchantSignature;
    /** @var float */
    public $amount;
    /** @var string */
    public $currency;
    /** @var string */
    public $authCode;
    /** @var string */
    public $email;
    /** @var string */
    public $phone;
    /** @var int */
    public $createdDate;
    /** @var int */
    public $processingDate;
    /** @var string */
    public $cardPan;
    /** @var string */
    public $cardType;
    /** @var string */
    public $issuerBankCountry;
    /** @var string */
    public $issuerBankName;
    /** @var string */
    public $recToken;
    /** @var string */
    public $transactionStatus;
    /** @var string */
    public $reason;
    /** @var int */
    public $reasonCode;
    /** @var int|float */
    public $fee;
    /** @var string */
    public $paymentSystem;
    /** @var string */
    public $cardProduct;
}
