<?php

namespace Parfums\SaleBundle\Request;

use Parfums\Api\Service\Request\DeserializableDTOInterface;

class KastaPay1CSettleRequest implements DeserializableDTOInterface
{
    /** @var float */
    public $amount;
    /** @var array */
    public $transactions = [];
    /** @var null|string */
    public $comment;
}
