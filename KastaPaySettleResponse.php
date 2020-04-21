<?php

namespace Parfums\SaleBundle\Response;

class KastaPaySettleResponse extends KastaPayResponse
{
    /** @var int */
    public $amount;
    /** @var string */
    public $currency;
    /** @var string */
    public $authCode;
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
    /** @var int */
    public $fee;
}
