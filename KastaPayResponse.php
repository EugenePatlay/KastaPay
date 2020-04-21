<?php

namespace Parfums\SaleBundle\Response;

use Parfums\Api\Service\Request\DeserializableDTOInterface;

class KastaPayResponse implements DeserializableDTOInterface
{
    /** @var string */
    public $merchantAccount;
    /** @var string */
    public $orderReference;
    /** @var string */
    public $transactionStatus;
    /** @var string */
    public $merchantSignature;
    /** @var string */
    public $reason;
    /** @var int */
    public $reasonCode;
}
